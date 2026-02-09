from fastapi import FastAPI, APIRouter, HTTPException, Depends, UploadFile, File, Query, BackgroundTasks
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from dotenv import load_dotenv
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
import os
import logging
from pathlib import Path
from pydantic import BaseModel, Field, EmailStr
from typing import List, Optional, Dict, Any
import uuid
from datetime import datetime, timezone, timedelta
import jwt
import bcrypt
import base64
import cv2
import numpy as np
from io import BytesIO
from PIL import Image
import json
from reportlab.lib import colors
from reportlab.lib.pagesizes import letter, A4
from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer
from reportlab.lib.styles import getSampleStyleSheet
from openpyxl import Workbook
from fastapi.responses import StreamingResponse

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

# MongoDB connection
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

# Create directories for storing face data
FACE_DATA_DIR = ROOT_DIR / "face_data"
FACE_DATA_DIR.mkdir(exist_ok=True)
ATTENDANCE_PHOTOS_DIR = ROOT_DIR / "attendance_photos"
ATTENDANCE_PHOTOS_DIR.mkdir(exist_ok=True)

# JWT Configuration
JWT_SECRET = os.environ.get('JWT_SECRET', 'smartattendance_secret_key_2024')
JWT_ALGORITHM = 'HS256'
JWT_EXPIRATION_HOURS = 24

# Twilio Configuration (optional)
TWILIO_ACCOUNT_SID = os.environ.get('TWILIO_ACCOUNT_SID', '')
TWILIO_AUTH_TOKEN = os.environ.get('TWILIO_AUTH_TOKEN', '')
TWILIO_PHONE_NUMBER = os.environ.get('TWILIO_PHONE_NUMBER', '')

# SMS functionality
SMS_ENABLED = bool(TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN and TWILIO_PHONE_NUMBER)

if SMS_ENABLED:
    from twilio.rest import Client as TwilioClient
    twilio_client = TwilioClient(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN)
else:
    twilio_client = None

# Load OpenCV face cascade
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')

app = FastAPI(title="SmartAttendance AI")
api_router = APIRouter(prefix="/api")
security = HTTPBearer()

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# ============ MODELS ============

class UserRole:
    ADMIN = "admin"
    TEACHER = "teacher"
    STUDENT = "student"
    PARENT = "parent"

class UserCreate(BaseModel):
    email: EmailStr
    password: str
    name: str
    role: str = UserRole.STUDENT
    phone: Optional[str] = None
    parent_phone: Optional[str] = None  # For students
    parent_email: Optional[str] = None

class UserLogin(BaseModel):
    email: EmailStr
    password: str

class UserResponse(BaseModel):
    id: str
    email: str
    name: str
    role: str
    phone: Optional[str] = None
    parent_phone: Optional[str] = None
    parent_email: Optional[str] = None
    created_at: str
    face_registered: bool = False

class TokenResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"
    user: UserResponse

class ClassCreate(BaseModel):
    name: str
    subject: str
    description: Optional[str] = None
    schedule: Optional[Dict[str, Any]] = None  # e.g., {"day": "Monday", "start": "09:00", "end": "10:00"}
    teacher_id: Optional[str] = None
    grace_period_minutes: int = 15  # Late arrival grace period

class ClassResponse(BaseModel):
    id: str
    name: str
    subject: str
    description: Optional[str] = None
    schedule: Optional[Dict[str, Any]] = None
    teacher_id: Optional[str] = None
    teacher_name: Optional[str] = None
    grace_period_minutes: int = 15
    student_count: int = 0
    created_at: str

class StudentClassEnrollment(BaseModel):
    student_id: str
    class_id: str

class FaceRegistration(BaseModel):
    user_id: str
    face_images: List[str]  # Base64 encoded images

class AttendanceMarkRequest(BaseModel):
    class_id: str
    face_image: str  # Base64 encoded image
    device_id: Optional[str] = None

class AttendanceResponse(BaseModel):
    id: str
    student_id: str
    student_name: str
    class_id: str
    class_name: str
    status: str  # present, absent, late
    timestamp: str
    photo_url: Optional[str] = None
    marked_by: Optional[str] = None
    device_info: Optional[str] = None

class AttendanceManualMark(BaseModel):
    student_id: str
    class_id: str
    status: str = "present"
    notes: Optional[str] = None

class SMSNotification(BaseModel):
    to_phone: str
    message: str

class SettingsUpdate(BaseModel):
    grace_period_minutes: Optional[int] = None
    sms_notifications_enabled: Optional[bool] = None
    late_threshold_minutes: Optional[int] = None

class PasswordResetRequest(BaseModel):
    email: EmailStr

class PasswordResetVerify(BaseModel):
    token: str
    new_password: str

class PasswordChange(BaseModel):
    current_password: str
    new_password: str

# ============ UTILITY FUNCTIONS ============

def hash_password(password: str) -> str:
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

def verify_password(password: str, hashed: str) -> bool:
    return bcrypt.checkpw(password.encode('utf-8'), hashed.encode('utf-8'))

def create_token(user_id: str, role: str) -> str:
    payload = {
        'user_id': user_id,
        'role': role,
        'exp': datetime.now(timezone.utc) + timedelta(hours=JWT_EXPIRATION_HOURS)
    }
    return jwt.encode(payload, JWT_SECRET, algorithm=JWT_ALGORITHM)

def decode_token(token: str) -> dict:
    try:
        return jwt.decode(token, JWT_SECRET, algorithms=[JWT_ALGORITHM])
    except jwt.ExpiredSignatureError:
        raise HTTPException(status_code=401, detail="Token expired")
    except jwt.InvalidTokenError:
        raise HTTPException(status_code=401, detail="Invalid token")

async def get_current_user(credentials: HTTPAuthorizationCredentials = Depends(security)):
    payload = decode_token(credentials.credentials)
    user = await db.users.find_one({"id": payload['user_id']}, {"_id": 0})
    if not user:
        raise HTTPException(status_code=401, detail="User not found")
    return user

def base64_to_image(base64_str: str) -> np.ndarray:
    """Convert base64 string to OpenCV image"""
    if ',' in base64_str:
        base64_str = base64_str.split(',')[1]
    img_data = base64.b64decode(base64_str)
    img = Image.open(BytesIO(img_data))
    return cv2.cvtColor(np.array(img), cv2.COLOR_RGB2BGR)

def detect_face(image: np.ndarray) -> bool:
    """Detect if a face is present in the image"""
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))
    return len(faces) > 0

def extract_face_features(image: np.ndarray) -> Optional[np.ndarray]:
    """Extract face region and compute a simple feature vector"""
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))
    
    if len(faces) == 0:
        return None
    
    # Get the largest face
    x, y, w, h = max(faces, key=lambda f: f[2] * f[3])
    face_roi = gray[y:y+h, x:x+w]
    
    # Resize to standard size for comparison
    face_resized = cv2.resize(face_roi, (100, 100))
    
    # Flatten as feature vector
    return face_resized.flatten().astype(np.float32)

def compare_faces(features1: np.ndarray, features2: np.ndarray, threshold: float = 0.7) -> float:
    """Compare two face feature vectors using correlation"""
    # Normalize
    f1 = (features1 - np.mean(features1)) / (np.std(features1) + 1e-8)
    f2 = (features2 - np.mean(features2)) / (np.std(features2) + 1e-8)
    
    # Correlation coefficient
    correlation = np.corrcoef(f1, f2)[0, 1]
    return float(correlation) if not np.isnan(correlation) else 0.0

async def send_sms_notification(to_phone: str, message: str) -> dict:
    """Send SMS notification via Twilio or mock"""
    if SMS_ENABLED and twilio_client:
        try:
            msg = twilio_client.messages.create(
                body=message,
                from_=TWILIO_PHONE_NUMBER,
                to=to_phone
            )
            return {"success": True, "message_sid": msg.sid}
        except Exception as e:
            logger.error(f"SMS send failed: {e}")
            return {"success": False, "error": str(e)}
    else:
        # Mock SMS - log it
        logger.info(f"[MOCK SMS] To: {to_phone}, Message: {message}")
        # Store in database for tracking
        await db.sms_logs.insert_one({
            "id": str(uuid.uuid4()),
            "to_phone": to_phone,
            "message": message,
            "status": "mocked",
            "timestamp": datetime.now(timezone.utc).isoformat()
        })
        return {"success": True, "mocked": True}

async def notify_parent_attendance(student_id: str, status: str, class_name: str):
    """Notify parent about student attendance"""
    student = await db.users.find_one({"id": student_id}, {"_id": 0})
    if not student or not student.get('parent_phone'):
        return
    
    student_name = student.get('name', 'Your child')
    
    if status == "present":
        message = f"SmartAttendance: {student_name} has been marked PRESENT for {class_name} at {datetime.now().strftime('%H:%M')}."
    elif status == "late":
        message = f"SmartAttendance: {student_name} arrived LATE to {class_name} at {datetime.now().strftime('%H:%M')}."
    else:
        message = f"SmartAttendance: {student_name} is marked ABSENT for {class_name}."
    
    await send_sms_notification(student['parent_phone'], message)

# ============ AUTH ROUTES ============

@api_router.post("/auth/register", response_model=TokenResponse)
async def register(user: UserCreate):
    # Check if user exists
    existing = await db.users.find_one({"email": user.email})
    if existing:
        raise HTTPException(status_code=400, detail="Email already registered")
    
    user_id = str(uuid.uuid4())
    user_dict = {
        "id": user_id,
        "email": user.email,
        "password": hash_password(user.password),
        "name": user.name,
        "role": user.role,
        "phone": user.phone,
        "parent_phone": user.parent_phone,
        "parent_email": user.parent_email,
        "face_registered": False,
        "face_features": None,
        "created_at": datetime.now(timezone.utc).isoformat()
    }
    
    await db.users.insert_one(user_dict)
    
    token = create_token(user_id, user.role)
    
    return TokenResponse(
        access_token=token,
        user=UserResponse(
            id=user_id,
            email=user.email,
            name=user.name,
            role=user.role,
            phone=user.phone,
            parent_phone=user.parent_phone,
            parent_email=user.parent_email,
            created_at=user_dict['created_at'],
            face_registered=False
        )
    )

@api_router.post("/auth/login", response_model=TokenResponse)
async def login(credentials: UserLogin):
    user = await db.users.find_one({"email": credentials.email}, {"_id": 0})
    if not user or not verify_password(credentials.password, user['password']):
        raise HTTPException(status_code=401, detail="Invalid credentials")
    
    token = create_token(user['id'], user['role'])
    
    return TokenResponse(
        access_token=token,
        user=UserResponse(
            id=user['id'],
            email=user['email'],
            name=user['name'],
            role=user['role'],
            phone=user.get('phone'),
            parent_phone=user.get('parent_phone'),
            parent_email=user.get('parent_email'),
            created_at=user['created_at'],
            face_registered=user.get('face_registered', False)
        )
    )

@api_router.get("/auth/me", response_model=UserResponse)
async def get_me(current_user: dict = Depends(get_current_user)):
    return UserResponse(
        id=current_user['id'],
        email=current_user['email'],
        name=current_user['name'],
        role=current_user['role'],
        phone=current_user.get('phone'),
        parent_phone=current_user.get('parent_phone'),
        parent_email=current_user.get('parent_email'),
        created_at=current_user['created_at'],
        face_registered=current_user.get('face_registered', False)
    )

# ============ USER MANAGEMENT ROUTES ============

@api_router.get("/users", response_model=List[UserResponse])
async def get_users(
    role: Optional[str] = None,
    search: Optional[str] = None,
    current_user: dict = Depends(get_current_user)
):
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    query = {}
    if role:
        query['role'] = role
    if search:
        query['$or'] = [
            {"name": {"$regex": search, "$options": "i"}},
            {"email": {"$regex": search, "$options": "i"}}
        ]
    
    users = await db.users.find(query, {"_id": 0, "password": 0, "face_features": 0}).to_list(1000)
    return [UserResponse(**u) for u in users]

@api_router.get("/users/{user_id}", response_model=UserResponse)
async def get_user(user_id: str, current_user: dict = Depends(get_current_user)):
    user = await db.users.find_one({"id": user_id}, {"_id": 0, "password": 0, "face_features": 0})
    if not user:
        raise HTTPException(status_code=404, detail="User not found")
    return UserResponse(**user)

@api_router.put("/users/{user_id}")
async def update_user(user_id: str, updates: dict, current_user: dict = Depends(get_current_user)):
    if current_user['role'] != UserRole.ADMIN and current_user['id'] != user_id:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    # Remove protected fields
    updates.pop('id', None)
    updates.pop('password', None)
    updates.pop('face_features', None)
    
    result = await db.users.update_one({"id": user_id}, {"$set": updates})
    if result.matched_count == 0:
        raise HTTPException(status_code=404, detail="User not found")
    
    return {"message": "User updated successfully"}

@api_router.delete("/users/{user_id}")
async def delete_user(user_id: str, current_user: dict = Depends(get_current_user)):
    if current_user['role'] != UserRole.ADMIN:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    result = await db.users.delete_one({"id": user_id})
    if result.deleted_count == 0:
        raise HTTPException(status_code=404, detail="User not found")
    
    # Also remove from class enrollments
    await db.class_enrollments.delete_many({"student_id": user_id})
    
    return {"message": "User deleted successfully"}

# ============ FACE REGISTRATION ROUTES ============

@api_router.post("/face/register")
async def register_face(data: FaceRegistration, current_user: dict = Depends(get_current_user)):
    """Register face for a user"""
    # Only admin/teacher can register others, students can register themselves
    if current_user['role'] == UserRole.STUDENT and current_user['id'] != data.user_id:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    if len(data.face_images) < 1:
        raise HTTPException(status_code=400, detail="At least 1 face image required")
    
    # Extract features from all images and average them
    all_features = []
    for img_base64 in data.face_images[:5]:  # Max 5 images
        try:
            img = base64_to_image(img_base64)
            features = extract_face_features(img)
            if features is not None:
                all_features.append(features)
        except Exception as e:
            logger.error(f"Error processing face image: {e}")
            continue
    
    if len(all_features) == 0:
        raise HTTPException(status_code=400, detail="No face detected in any of the images")
    
    # Average the features
    avg_features = np.mean(all_features, axis=0)
    
    # Store as base64 encoded numpy array
    features_b64 = base64.b64encode(avg_features.tobytes()).decode('utf-8')
    
    await db.users.update_one(
        {"id": data.user_id},
        {"$set": {"face_registered": True, "face_features": features_b64}}
    )
    
    return {"message": "Face registered successfully", "images_processed": len(all_features)}

@api_router.post("/face/detect")
async def detect_face_endpoint(face_image: str = Query(...)):
    """Check if a face is detected in the image"""
    try:
        img = base64_to_image(face_image)
        has_face = detect_face(img)
        return {"face_detected": has_face}
    except Exception as e:
        return {"face_detected": False, "error": str(e)}

@api_router.post("/face/recognize")
async def recognize_face(class_id: str, face_image: str, current_user: dict = Depends(get_current_user)):
    """Recognize a face from enrolled students in a class"""
    try:
        img = base64_to_image(face_image)
        features = extract_face_features(img)
        
        if features is None:
            return {"recognized": False, "message": "No face detected"}
        
        # Get all enrolled students in this class
        enrollments = await db.class_enrollments.find({"class_id": class_id}, {"_id": 0}).to_list(1000)
        student_ids = [e['student_id'] for e in enrollments]
        
        # Get students with registered faces
        students = await db.users.find(
            {"id": {"$in": student_ids}, "face_registered": True},
            {"_id": 0}
        ).to_list(1000)
        
        best_match = None
        best_score = 0.0
        threshold = 0.6  # Minimum correlation for match
        
        for student in students:
            if not student.get('face_features'):
                continue
            
            # Decode stored features
            stored_features = np.frombuffer(
                base64.b64decode(student['face_features']),
                dtype=np.float32
            )
            
            score = compare_faces(features, stored_features)
            
            if score > best_score and score >= threshold:
                best_score = score
                best_match = student
        
        if best_match:
            return {
                "recognized": True,
                "student_id": best_match['id'],
                "student_name": best_match['name'],
                "confidence": round(best_score * 100, 2)
            }
        
        return {"recognized": False, "message": "No matching student found"}
        
    except Exception as e:
        logger.error(f"Face recognition error: {e}")
        return {"recognized": False, "error": str(e)}

# ============ CLASS MANAGEMENT ROUTES ============

@api_router.post("/classes", response_model=ClassResponse)
async def create_class(class_data: ClassCreate, current_user: dict = Depends(get_current_user)):
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    class_id = str(uuid.uuid4())
    teacher_id = class_data.teacher_id or (current_user['id'] if current_user['role'] == UserRole.TEACHER else None)
    
    teacher_name = None
    if teacher_id:
        teacher = await db.users.find_one({"id": teacher_id}, {"_id": 0})
        teacher_name = teacher['name'] if teacher else None
    
    class_dict = {
        "id": class_id,
        "name": class_data.name,
        "subject": class_data.subject,
        "description": class_data.description,
        "schedule": class_data.schedule,
        "teacher_id": teacher_id,
        "teacher_name": teacher_name,
        "grace_period_minutes": class_data.grace_period_minutes,
        "created_at": datetime.now(timezone.utc).isoformat()
    }
    
    await db.classes.insert_one(class_dict)
    
    return ClassResponse(**class_dict, student_count=0)

@api_router.get("/classes", response_model=List[ClassResponse])
async def get_classes(current_user: dict = Depends(get_current_user)):
    query = {}
    
    # Teachers only see their classes
    if current_user['role'] == UserRole.TEACHER:
        query['teacher_id'] = current_user['id']
    # Students see classes they're enrolled in
    elif current_user['role'] == UserRole.STUDENT:
        enrollments = await db.class_enrollments.find(
            {"student_id": current_user['id']}, {"_id": 0}
        ).to_list(1000)
        class_ids = [e['class_id'] for e in enrollments]
        query['id'] = {"$in": class_ids}
    
    classes = await db.classes.find(query, {"_id": 0}).to_list(1000)
    
    # Add student counts
    for cls in classes:
        count = await db.class_enrollments.count_documents({"class_id": cls['id']})
        cls['student_count'] = count
    
    return [ClassResponse(**c) for c in classes]

@api_router.get("/classes/{class_id}", response_model=ClassResponse)
async def get_class(class_id: str, current_user: dict = Depends(get_current_user)):
    cls = await db.classes.find_one({"id": class_id}, {"_id": 0})
    if not cls:
        raise HTTPException(status_code=404, detail="Class not found")
    
    count = await db.class_enrollments.count_documents({"class_id": class_id})
    cls['student_count'] = count
    
    return ClassResponse(**cls)

@api_router.put("/classes/{class_id}")
async def update_class(class_id: str, updates: dict, current_user: dict = Depends(get_current_user)):
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    updates.pop('id', None)
    result = await db.classes.update_one({"id": class_id}, {"$set": updates})
    if result.matched_count == 0:
        raise HTTPException(status_code=404, detail="Class not found")
    
    return {"message": "Class updated successfully"}

@api_router.delete("/classes/{class_id}")
async def delete_class(class_id: str, current_user: dict = Depends(get_current_user)):
    if current_user['role'] != UserRole.ADMIN:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    result = await db.classes.delete_one({"id": class_id})
    if result.deleted_count == 0:
        raise HTTPException(status_code=404, detail="Class not found")
    
    # Remove enrollments
    await db.class_enrollments.delete_many({"class_id": class_id})
    
    return {"message": "Class deleted successfully"}

# ============ CLASS ENROLLMENT ROUTES ============

@api_router.post("/classes/{class_id}/enroll")
async def enroll_student(class_id: str, enrollment: StudentClassEnrollment, current_user: dict = Depends(get_current_user)):
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    # Check if already enrolled
    existing = await db.class_enrollments.find_one({
        "class_id": class_id,
        "student_id": enrollment.student_id
    })
    if existing:
        raise HTTPException(status_code=400, detail="Student already enrolled")
    
    enrollment_dict = {
        "id": str(uuid.uuid4()),
        "class_id": class_id,
        "student_id": enrollment.student_id,
        "enrolled_at": datetime.now(timezone.utc).isoformat()
    }
    
    await db.class_enrollments.insert_one(enrollment_dict)
    return {"message": "Student enrolled successfully"}

@api_router.delete("/classes/{class_id}/enroll/{student_id}")
async def unenroll_student(class_id: str, student_id: str, current_user: dict = Depends(get_current_user)):
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    result = await db.class_enrollments.delete_one({
        "class_id": class_id,
        "student_id": student_id
    })
    
    if result.deleted_count == 0:
        raise HTTPException(status_code=404, detail="Enrollment not found")
    
    return {"message": "Student unenrolled successfully"}

@api_router.get("/classes/{class_id}/students")
async def get_class_students(class_id: str, current_user: dict = Depends(get_current_user)):
    enrollments = await db.class_enrollments.find({"class_id": class_id}, {"_id": 0}).to_list(1000)
    student_ids = [e['student_id'] for e in enrollments]
    
    students = await db.users.find(
        {"id": {"$in": student_ids}},
        {"_id": 0, "password": 0, "face_features": 0}
    ).to_list(1000)
    
    return students

# ============ ATTENDANCE ROUTES ============

@api_router.post("/attendance/mark", response_model=AttendanceResponse)
async def mark_attendance(
    data: AttendanceMarkRequest,
    background_tasks: BackgroundTasks,
    current_user: dict = Depends(get_current_user)
):
    """Mark attendance using face recognition"""
    try:
        img = base64_to_image(data.face_image)
        features = extract_face_features(img)
        
        if features is None:
            raise HTTPException(status_code=400, detail="No face detected in image")
        
        # Get class info
        cls = await db.classes.find_one({"id": data.class_id}, {"_id": 0})
        if not cls:
            raise HTTPException(status_code=404, detail="Class not found")
        
        # Get enrolled students
        enrollments = await db.class_enrollments.find({"class_id": data.class_id}, {"_id": 0}).to_list(1000)
        student_ids = [e['student_id'] for e in enrollments]
        
        students = await db.users.find(
            {"id": {"$in": student_ids}, "face_registered": True},
            {"_id": 0}
        ).to_list(1000)
        
        # Find matching student
        best_match = None
        best_score = 0.0
        threshold = 0.55
        
        for student in students:
            if not student.get('face_features'):
                continue
            
            stored_features = np.frombuffer(
                base64.b64decode(student['face_features']),
                dtype=np.float32
            )
            
            score = compare_faces(features, stored_features)
            
            if score > best_score and score >= threshold:
                best_score = score
                best_match = student
        
        if not best_match:
            raise HTTPException(status_code=404, detail="No matching student found")
        
        # Check if already marked today
        today_start = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
        existing = await db.attendance.find_one({
            "student_id": best_match['id'],
            "class_id": data.class_id,
            "timestamp": {"$gte": today_start.isoformat()}
        })
        
        if existing:
            raise HTTPException(status_code=400, detail="Attendance already marked for today")
        
        # Determine status (present or late)
        now = datetime.now(timezone.utc)
        status = "present"
        
        if cls.get('schedule') and cls['schedule'].get('start'):
            try:
                schedule_start = datetime.strptime(cls['schedule']['start'], "%H:%M").time()
                current_time = now.time()
                
                # Calculate grace period
                grace_minutes = cls.get('grace_period_minutes', 15)
                grace_time = (datetime.combine(datetime.today(), schedule_start) + timedelta(minutes=grace_minutes)).time()
                
                if current_time > grace_time:
                    status = "late"
            except:
                pass
        
        # Save attendance photo
        photo_filename = f"{best_match['id']}_{data.class_id}_{now.strftime('%Y%m%d_%H%M%S')}.jpg"
        photo_path = ATTENDANCE_PHOTOS_DIR / photo_filename
        cv2.imwrite(str(photo_path), img)
        
        attendance_id = str(uuid.uuid4())
        attendance_dict = {
            "id": attendance_id,
            "student_id": best_match['id'],
            "student_name": best_match['name'],
            "class_id": data.class_id,
            "class_name": cls['name'],
            "status": status,
            "timestamp": now.isoformat(),
            "photo_filename": photo_filename,
            "confidence": round(best_score * 100, 2),
            "marked_by": current_user['id'],
            "device_id": data.device_id
        }
        
        await db.attendance.insert_one(attendance_dict)
        
        # Send SMS notification in background
        background_tasks.add_task(notify_parent_attendance, best_match['id'], status, cls['name'])
        
        return AttendanceResponse(
            id=attendance_id,
            student_id=best_match['id'],
            student_name=best_match['name'],
            class_id=data.class_id,
            class_name=cls['name'],
            status=status,
            timestamp=now.isoformat(),
            photo_url=f"/api/attendance/photo/{photo_filename}",
            marked_by=current_user['name']
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Attendance marking error: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@api_router.post("/attendance/manual", response_model=AttendanceResponse)
async def mark_attendance_manual(
    data: AttendanceManualMark,
    background_tasks: BackgroundTasks,
    current_user: dict = Depends(get_current_user)
):
    """Manually mark attendance without face recognition"""
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    # Get student and class
    student = await db.users.find_one({"id": data.student_id}, {"_id": 0, "password": 0, "face_features": 0})
    cls = await db.classes.find_one({"id": data.class_id}, {"_id": 0})
    
    if not student or not cls:
        raise HTTPException(status_code=404, detail="Student or class not found")
    
    # Check if already marked today
    today_start = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
    existing = await db.attendance.find_one({
        "student_id": data.student_id,
        "class_id": data.class_id,
        "timestamp": {"$gte": today_start.isoformat()}
    })
    
    if existing:
        raise HTTPException(status_code=400, detail="Attendance already marked for today")
    
    now = datetime.now(timezone.utc)
    attendance_id = str(uuid.uuid4())
    
    attendance_dict = {
        "id": attendance_id,
        "student_id": data.student_id,
        "student_name": student['name'],
        "class_id": data.class_id,
        "class_name": cls['name'],
        "status": data.status,
        "timestamp": now.isoformat(),
        "notes": data.notes,
        "marked_by": current_user['id'],
        "manual": True
    }
    
    await db.attendance.insert_one(attendance_dict)
    
    # Send SMS notification
    background_tasks.add_task(notify_parent_attendance, data.student_id, data.status, cls['name'])
    
    return AttendanceResponse(
        id=attendance_id,
        student_id=data.student_id,
        student_name=student['name'],
        class_id=data.class_id,
        class_name=cls['name'],
        status=data.status,
        timestamp=now.isoformat(),
        marked_by=current_user['name']
    )

@api_router.get("/attendance", response_model=List[AttendanceResponse])
async def get_attendance(
    class_id: Optional[str] = None,
    student_id: Optional[str] = None,
    date_from: Optional[str] = None,
    date_to: Optional[str] = None,
    status: Optional[str] = None,
    current_user: dict = Depends(get_current_user)
):
    query = {}
    
    if current_user['role'] == UserRole.STUDENT:
        query['student_id'] = current_user['id']
    elif student_id:
        query['student_id'] = student_id
    
    if class_id:
        query['class_id'] = class_id
    
    if date_from:
        query['timestamp'] = {"$gte": date_from}
    if date_to:
        if 'timestamp' in query:
            query['timestamp']['$lte'] = date_to
        else:
            query['timestamp'] = {"$lte": date_to}
    
    if status:
        query['status'] = status
    
    records = await db.attendance.find(query, {"_id": 0}).sort("timestamp", -1).to_list(1000)
    
    return [AttendanceResponse(
        id=r['id'],
        student_id=r['student_id'],
        student_name=r['student_name'],
        class_id=r['class_id'],
        class_name=r['class_name'],
        status=r['status'],
        timestamp=r['timestamp'],
        photo_url=f"/api/attendance/photo/{r['photo_filename']}" if r.get('photo_filename') else None,
        marked_by=r.get('marked_by')
    ) for r in records]

@api_router.get("/attendance/photo/{filename}")
async def get_attendance_photo(filename: str):
    photo_path = ATTENDANCE_PHOTOS_DIR / filename
    if not photo_path.exists():
        raise HTTPException(status_code=404, detail="Photo not found")
    
    return StreamingResponse(
        open(photo_path, "rb"),
        media_type="image/jpeg"
    )

@api_router.get("/attendance/today/{class_id}")
async def get_today_attendance(class_id: str, current_user: dict = Depends(get_current_user)):
    """Get attendance status for all students in a class for today"""
    today_start = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
    
    # Get enrolled students
    enrollments = await db.class_enrollments.find({"class_id": class_id}, {"_id": 0}).to_list(1000)
    student_ids = [e['student_id'] for e in enrollments]
    
    students = await db.users.find(
        {"id": {"$in": student_ids}},
        {"_id": 0, "password": 0, "face_features": 0}
    ).to_list(1000)
    
    # Get today's attendance
    attendance = await db.attendance.find({
        "class_id": class_id,
        "timestamp": {"$gte": today_start.isoformat()}
    }, {"_id": 0}).to_list(1000)
    
    attendance_map = {a['student_id']: a for a in attendance}
    
    result = []
    for student in students:
        att = attendance_map.get(student['id'])
        result.append({
            "student_id": student['id'],
            "student_name": student['name'],
            "face_registered": student.get('face_registered', False),
            "status": att['status'] if att else "not_marked",
            "timestamp": att['timestamp'] if att else None,
            "photo_url": f"/api/attendance/photo/{att['photo_filename']}" if att and att.get('photo_filename') else None
        })
    
    return result

# ============ ANALYTICS ROUTES ============

@api_router.get("/analytics/overview")
async def get_analytics_overview(current_user: dict = Depends(get_current_user)):
    """Get overview analytics"""
    today = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
    week_ago = today - timedelta(days=7)
    month_ago = today - timedelta(days=30)
    
    # Total counts
    total_students = await db.users.count_documents({"role": UserRole.STUDENT})
    total_teachers = await db.users.count_documents({"role": UserRole.TEACHER})
    total_classes = await db.classes.count_documents({})
    
    # Today's attendance
    today_attendance = await db.attendance.find({
        "timestamp": {"$gte": today.isoformat()}
    }, {"_id": 0}).to_list(10000)
    
    today_present = len([a for a in today_attendance if a['status'] == 'present'])
    today_late = len([a for a in today_attendance if a['status'] == 'late'])
    today_absent = total_students - today_present - today_late  # Simplified
    
    # Weekly trend
    weekly_data = []
    for i in range(7):
        day = today - timedelta(days=6-i)
        next_day = day + timedelta(days=1)
        
        day_attendance = await db.attendance.count_documents({
            "timestamp": {"$gte": day.isoformat(), "$lt": next_day.isoformat()}
        })
        
        weekly_data.append({
            "date": day.strftime("%Y-%m-%d"),
            "day": day.strftime("%a"),
            "count": day_attendance
        })
    
    # Attendance rate
    total_month_records = await db.attendance.count_documents({
        "timestamp": {"$gte": month_ago.isoformat()}
    })
    present_month = await db.attendance.count_documents({
        "timestamp": {"$gte": month_ago.isoformat()},
        "status": {"$in": ["present", "late"]}
    })
    
    attendance_rate = round((present_month / total_month_records * 100) if total_month_records > 0 else 0, 1)
    
    return {
        "total_students": total_students,
        "total_teachers": total_teachers,
        "total_classes": total_classes,
        "today": {
            "present": today_present,
            "late": today_late,
            "absent": today_absent,
            "total": len(today_attendance)
        },
        "weekly_trend": weekly_data,
        "attendance_rate": attendance_rate,
        "faces_registered": await db.users.count_documents({"face_registered": True})
    }

@api_router.get("/analytics/class/{class_id}")
async def get_class_analytics(class_id: str, current_user: dict = Depends(get_current_user)):
    """Get analytics for a specific class"""
    month_ago = datetime.now(timezone.utc) - timedelta(days=30)
    
    # Get class
    cls = await db.classes.find_one({"id": class_id}, {"_id": 0})
    if not cls:
        raise HTTPException(status_code=404, detail="Class not found")
    
    # Get attendance records
    records = await db.attendance.find({
        "class_id": class_id,
        "timestamp": {"$gte": month_ago.isoformat()}
    }, {"_id": 0}).to_list(10000)
    
    # Calculate stats
    total = len(records)
    present = len([r for r in records if r['status'] == 'present'])
    late = len([r for r in records if r['status'] == 'late'])
    absent = len([r for r in records if r['status'] == 'absent'])
    
    # Student breakdown
    student_stats = {}
    for r in records:
        sid = r['student_id']
        if sid not in student_stats:
            student_stats[sid] = {"name": r['student_name'], "present": 0, "late": 0, "absent": 0}
        student_stats[sid][r['status']] += 1
    
    return {
        "class_id": class_id,
        "class_name": cls['name'],
        "total_records": total,
        "summary": {
            "present": present,
            "late": late,
            "absent": absent
        },
        "attendance_rate": round((present + late) / total * 100 if total > 0 else 0, 1),
        "student_breakdown": list(student_stats.values())
    }

# ============ REPORTS ROUTES ============

@api_router.get("/reports/export/excel")
async def export_attendance_excel(
    class_id: Optional[str] = None,
    date_from: Optional[str] = None,
    date_to: Optional[str] = None,
    current_user: dict = Depends(get_current_user)
):
    """Export attendance to Excel"""
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    query = {}
    if class_id:
        query['class_id'] = class_id
    if date_from:
        query['timestamp'] = {"$gte": date_from}
    if date_to:
        if 'timestamp' in query:
            query['timestamp']['$lte'] = date_to
        else:
            query['timestamp'] = {"$lte": date_to}
    
    records = await db.attendance.find(query, {"_id": 0}).sort("timestamp", -1).to_list(10000)
    
    # Create Excel workbook
    wb = Workbook()
    ws = wb.active
    ws.title = "Attendance Report"
    
    # Headers
    headers = ["Date", "Time", "Student Name", "Class", "Status"]
    ws.append(headers)
    
    # Data
    for r in records:
        ts = datetime.fromisoformat(r['timestamp'].replace('Z', '+00:00'))
        ws.append([
            ts.strftime("%Y-%m-%d"),
            ts.strftime("%H:%M"),
            r['student_name'],
            r['class_name'],
            r['status'].upper()
        ])
    
    # Save to buffer
    buffer = BytesIO()
    wb.save(buffer)
    buffer.seek(0)
    
    return StreamingResponse(
        buffer,
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={"Content-Disposition": "attachment; filename=attendance_report.xlsx"}
    )

@api_router.get("/reports/export/pdf")
async def export_attendance_pdf(
    class_id: Optional[str] = None,
    date_from: Optional[str] = None,
    date_to: Optional[str] = None,
    current_user: dict = Depends(get_current_user)
):
    """Export attendance to PDF"""
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    query = {}
    if class_id:
        query['class_id'] = class_id
    if date_from:
        query['timestamp'] = {"$gte": date_from}
    if date_to:
        if 'timestamp' in query:
            query['timestamp']['$lte'] = date_to
        else:
            query['timestamp'] = {"$lte": date_to}
    
    records = await db.attendance.find(query, {"_id": 0}).sort("timestamp", -1).to_list(10000)
    
    # Create PDF
    buffer = BytesIO()
    doc = SimpleDocTemplate(buffer, pagesize=A4)
    elements = []
    styles = getSampleStyleSheet()
    
    # Title
    elements.append(Paragraph("Attendance Report", styles['Title']))
    elements.append(Spacer(1, 20))
    
    # Date range
    if date_from or date_to:
        date_str = f"Period: {date_from or 'Start'} to {date_to or 'Now'}"
        elements.append(Paragraph(date_str, styles['Normal']))
        elements.append(Spacer(1, 10))
    
    # Table data
    table_data = [["Date", "Time", "Student", "Class", "Status"]]
    for r in records[:100]:  # Limit to 100 records for PDF
        ts = datetime.fromisoformat(r['timestamp'].replace('Z', '+00:00'))
        table_data.append([
            ts.strftime("%Y-%m-%d"),
            ts.strftime("%H:%M"),
            r['student_name'][:20],
            r['class_name'][:15],
            r['status'].upper()
        ])
    
    # Create table
    table = Table(table_data, colWidths=[70, 50, 120, 100, 60])
    table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#4338CA')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, 0), 10),
        ('BOTTOMPADDING', (0, 0), (-1, 0), 12),
        ('BACKGROUND', (0, 1), (-1, -1), colors.HexColor('#F8FAFC')),
        ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#E2E8F0'))
    ]))
    
    elements.append(table)
    doc.build(elements)
    buffer.seek(0)
    
    return StreamingResponse(
        buffer,
        media_type="application/pdf",
        headers={"Content-Disposition": "attachment; filename=attendance_report.pdf"}
    )

# ============ SMS ROUTES ============

@api_router.post("/sms/send")
async def send_sms(notification: SMSNotification, current_user: dict = Depends(get_current_user)):
    """Send SMS notification"""
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    result = await send_sms_notification(notification.to_phone, notification.message)
    return result

@api_router.get("/sms/logs")
async def get_sms_logs(current_user: dict = Depends(get_current_user)):
    """Get SMS notification logs"""
    if current_user['role'] not in [UserRole.ADMIN, UserRole.TEACHER]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    logs = await db.sms_logs.find({}, {"_id": 0}).sort("timestamp", -1).to_list(100)
    return logs

@api_router.get("/sms/status")
async def get_sms_status():
    """Check if SMS is enabled"""
    return {"sms_enabled": SMS_ENABLED, "provider": "twilio" if SMS_ENABLED else "mocked"}

# ============ SETTINGS ROUTES ============

@api_router.get("/settings")
async def get_settings(current_user: dict = Depends(get_current_user)):
    """Get system settings"""
    settings = await db.settings.find_one({"type": "system"}, {"_id": 0, "type": 0})
    if not settings:
        default_settings = {
            "type": "system",
            "grace_period_minutes": 15,
            "sms_notifications_enabled": True,
            "late_threshold_minutes": 30
        }
        await db.settings.insert_one(default_settings)
        # Return without 'type' field
        settings = {
            "grace_period_minutes": 15,
            "sms_notifications_enabled": True,
            "late_threshold_minutes": 30
        }
    return settings

@api_router.put("/settings")
async def update_settings(updates: SettingsUpdate, current_user: dict = Depends(get_current_user)):
    """Update system settings"""
    if current_user['role'] != UserRole.ADMIN:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    update_dict = {k: v for k, v in updates.model_dump().items() if v is not None}
    
    await db.settings.update_one(
        {"type": "system"},
        {"$set": update_dict},
        upsert=True
    )
    
    return {"message": "Settings updated successfully"}

# ============ DASHBOARD STATS ============

@api_router.get("/dashboard/stats")
async def get_dashboard_stats(current_user: dict = Depends(get_current_user)):
    """Get dashboard statistics based on user role"""
    today = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
    
    if current_user['role'] == UserRole.STUDENT:
        # Student sees their own stats
        total_classes = await db.class_enrollments.count_documents({"student_id": current_user['id']})
        
        month_ago = today - timedelta(days=30)
        my_attendance = await db.attendance.find({
            "student_id": current_user['id'],
            "timestamp": {"$gte": month_ago.isoformat()}
        }, {"_id": 0}).to_list(1000)
        
        present_count = len([a for a in my_attendance if a['status'] in ['present', 'late']])
        total_count = len(my_attendance) if my_attendance else 1
        
        return {
            "enrolled_classes": total_classes,
            "attendance_rate": round(present_count / total_count * 100, 1),
            "total_present": present_count,
            "total_late": len([a for a in my_attendance if a['status'] == 'late']),
            "total_absent": len([a for a in my_attendance if a['status'] == 'absent'])
        }
    
    elif current_user['role'] == UserRole.TEACHER:
        # Teacher sees their classes stats
        my_classes = await db.classes.find({"teacher_id": current_user['id']}, {"_id": 0}).to_list(100)
        class_ids = [c['id'] for c in my_classes]
        
        total_students = await db.class_enrollments.count_documents({"class_id": {"$in": class_ids}})
        
        today_attendance = await db.attendance.count_documents({
            "class_id": {"$in": class_ids},
            "timestamp": {"$gte": today.isoformat()}
        })
        
        return {
            "total_classes": len(my_classes),
            "total_students": total_students,
            "today_attendance": today_attendance
        }
    
    else:
        # Admin sees everything
        return await get_analytics_overview(current_user)

# Root endpoint
@api_router.get("/")
async def root():
    return {"message": "SmartAttendance AI API", "version": "1.0.0"}

# Include router
app.include_router(api_router)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.on_event("shutdown")
async def shutdown_db_client():
    client.close()
