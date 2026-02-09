import requests
import sys
import json
import base64
from datetime import datetime
from PIL import Image
from io import BytesIO
import numpy as np

class SmartAttendanceAPITester:
    def __init__(self, base_url="https://student-presence-ai.preview.emergentagent.com"):
        self.base_url = base_url
        self.token = None
        self.headers = {'Content-Type': 'application/json'}
        self.tests_run = 0
        self.tests_passed = 0
        self.admin_user = None
        self.teacher_user = None
        self.student_user = None
        self.test_class_id = None

    def log_result(self, name, success, message=""):
        """Log test result"""
        self.tests_run += 1
        status = "✅ PASSED" if success else "❌ FAILED"
        print(f"{status} - {name}")
        if message:
            print(f"   {message}")
        if success:
            self.tests_passed += 1
        print()

    def run_request(self, method, endpoint, data=None, expected_status=200, headers=None):
        """Execute API request"""
        url = f"{self.base_url}/api/{endpoint}"
        request_headers = headers or self.headers.copy()
        
        if self.token:
            request_headers['Authorization'] = f'Bearer {self.token}'

        try:
            if method == 'GET':
                response = requests.get(url, headers=request_headers, timeout=10)
            elif method == 'POST':
                response = requests.post(url, json=data, headers=request_headers, timeout=10)
            elif method == 'PUT':
                response = requests.put(url, json=data, headers=request_headers, timeout=10)
            elif method == 'DELETE':
                response = requests.delete(url, headers=request_headers, timeout=10)
            else:
                return False, {"error": f"Unsupported method: {method}"}

            success = response.status_code == expected_status
            try:
                response_data = response.json()
            except:
                response_data = {"status": response.status_code, "text": response.text}

            return success, response_data

        except requests.exceptions.Timeout:
            return False, {"error": "Request timeout"}
        except requests.exceptions.ConnectionError:
            return False, {"error": "Connection failed"}
        except Exception as e:
            return False, {"error": str(e)}

    def test_root_endpoint(self):
        """Test root API endpoint"""
        success, response = self.run_request('GET', '')
        expected = response.get('message') == 'SmartAttendance AI API'
        self.log_result("Root API Endpoint", success and expected, 
                       f"Response: {response}")
        return success and expected

    def test_user_registration(self):
        """Test user registration for all roles"""
        timestamp = datetime.now().strftime("%H%M%S")
        
        # Test Admin Registration
        admin_data = {
            "email": f"admin_{timestamp}@test.edu",
            "password": "TestAdmin123!",
            "name": "Test Admin",
            "role": "admin",
            "phone": "+1234567890"
        }
        
        success, response = self.run_request('POST', 'auth/register', admin_data, 200)
        if success:
            self.admin_user = response.get('user')
            self.token = response.get('access_token')
        self.log_result("Admin Registration", success, 
                       f"Admin ID: {self.admin_user.get('id') if self.admin_user else 'None'}")

        # Test Teacher Registration
        teacher_data = {
            "email": f"teacher_{timestamp}@test.edu",
            "password": "TestTeacher123!",
            "name": "Test Teacher",
            "role": "teacher",
            "phone": "+1234567891"
        }
        
        success, response = self.run_request('POST', 'auth/register', teacher_data, 200)
        if success:
            self.teacher_user = response.get('user')
        self.log_result("Teacher Registration", success,
                       f"Teacher ID: {self.teacher_user.get('id') if self.teacher_user else 'None'}")

        # Test Student Registration
        student_data = {
            "email": f"student_{timestamp}@test.edu",
            "password": "TestStudent123!",
            "name": "Test Student",
            "role": "student",
            "phone": "+1234567892",
            "parent_phone": "+1234567893",
            "parent_email": f"parent_{timestamp}@test.edu"
        }
        
        success, response = self.run_request('POST', 'auth/register', student_data, 200)
        if success:
            self.student_user = response.get('user')
        self.log_result("Student Registration", success,
                       f"Student ID: {self.student_user.get('id') if self.student_user else 'None'}")

        return True

    def test_authentication(self):
        """Test login functionality"""
        if not self.admin_user:
            self.log_result("Authentication Test", False, "No admin user available")
            return False

        # Test login
        login_data = {
            "email": self.admin_user['email'],
            "password": "TestAdmin123!"
        }
        
        success, response = self.run_request('POST', 'auth/login', login_data, 200)
        if success:
            self.token = response.get('access_token')
            
        self.log_result("User Login", success,
                       f"Token received: {'Yes' if self.token else 'No'}")

        # Test /auth/me endpoint
        if self.token:
            success, response = self.run_request('GET', 'auth/me', expected_status=200)
            self.log_result("Get Current User", success,
                           f"User: {response.get('name', 'Unknown')}")

        return True

    def test_class_management(self):
        """Test class CRUD operations"""
        if not self.token:
            self.log_result("Class Management", False, "No authentication token")
            return False

        # Create a class
        class_data = {
            "name": "Test Computer Science 101",
            "subject": "Computer Science",
            "description": "Introduction to Programming",
            "schedule": {
                "day": "Monday",
                "start": "09:00",
                "end": "10:00"
            },
            "grace_period_minutes": 15
        }

        success, response = self.run_request('POST', 'classes', class_data, 200)
        if success:
            self.test_class_id = response.get('id')
        self.log_result("Create Class", success,
                       f"Class ID: {self.test_class_id}")

        # Get all classes
        success, response = self.run_request('GET', 'classes', expected_status=200)
        classes_count = len(response) if isinstance(response, list) else 0
        self.log_result("Get All Classes", success,
                       f"Classes found: {classes_count}")

        # Get specific class
        if self.test_class_id:
            success, response = self.run_request('GET', f'classes/{self.test_class_id}', expected_status=200)
            self.log_result("Get Specific Class", success,
                           f"Class name: {response.get('name', 'Unknown')}")

        return True

    def test_user_management(self):
        """Test user management endpoints"""
        if not self.token:
            self.log_result("User Management", False, "No authentication token")
            return False

        # Get all users
        success, response = self.run_request('GET', 'users', expected_status=200)
        users_count = len(response) if isinstance(response, list) else 0
        self.log_result("Get All Users", success,
                       f"Users found: {users_count}")

        # Get users by role
        success, response = self.run_request('GET', 'users?role=student', expected_status=200)
        students_count = len(response) if isinstance(response, list) else 0
        self.log_result("Get Students", success,
                       f"Students found: {students_count}")

        return True

    def create_test_face_image(self):
        """Create a simple test face image as base64"""
        try:
            # Create a simple 100x100 RGB image with a circle (representing a face)
            img = Image.new('RGB', (100, 100), color='lightblue')
            pixels = img.load()
            
            # Draw a simple circle
            center_x, center_y = 50, 50
            radius = 30
            for x in range(100):
                for y in range(100):
                    if (x - center_x) ** 2 + (y - center_y) ** 2 <= radius ** 2:
                        pixels[x, y] = (255, 200, 200)  # Face color
            
            # Convert to base64
            buffer = BytesIO()
            img.save(buffer, format='JPEG')
            img_base64 = base64.b64encode(buffer.getvalue()).decode('utf-8')
            
            return f"data:image/jpeg;base64,{img_base64}"
        except Exception as e:
            print(f"Error creating test image: {e}")
            return None

    def test_face_detection(self):
        """Test face detection endpoint"""
        if not self.token:
            self.log_result("Face Detection", False, "No authentication token")
            return False

        test_image = self.create_test_face_image()
        if not test_image:
            self.log_result("Face Detection", False, "Could not create test image")
            return False

        # Test face detection
        try:
            url = f"{self.base_url}/api/face/detect?face_image={test_image}"
            headers = {'Authorization': f'Bearer {self.token}'}
            response = requests.post(url, headers=headers, timeout=10)
            
            success = response.status_code == 200
            if success:
                data = response.json()
                face_detected = data.get('face_detected', False)
                self.log_result("Face Detection API", success,
                               f"Face detected: {face_detected}")
            else:
                self.log_result("Face Detection API", False,
                               f"Status: {response.status_code}")
        except Exception as e:
            self.log_result("Face Detection API", False, f"Error: {str(e)}")

        return True

    def test_attendance_endpoints(self):
        """Test attendance-related endpoints"""
        if not self.token:
            self.log_result("Attendance Endpoints", False, "No authentication token")
            return False

        # Get attendance records
        success, response = self.run_request('GET', 'attendance', expected_status=200)
        attendance_count = len(response) if isinstance(response, list) else 0
        self.log_result("Get Attendance Records", success,
                       f"Records found: {attendance_count}")

        # Get today's attendance for a class
        if self.test_class_id:
            success, response = self.run_request('GET', f'attendance/today/{self.test_class_id}', expected_status=200)
            today_count = len(response) if isinstance(response, list) else 0
            self.log_result("Get Today's Attendance", success,
                           f"Today's records: {today_count}")

        return True

    def test_analytics_endpoints(self):
        """Test analytics endpoints"""
        if not self.token:
            self.log_result("Analytics Endpoints", False, "No authentication token")
            return False

        # Get analytics overview
        success, response = self.run_request('GET', 'analytics/overview', expected_status=200)
        self.log_result("Analytics Overview", success,
                       f"Total students: {response.get('total_students', 0) if success else 'Unknown'}")

        # Get dashboard stats
        success, response = self.run_request('GET', 'dashboard/stats', expected_status=200)
        self.log_result("Dashboard Stats", success,
                       f"Stats received: {'Yes' if success and response else 'No'}")

        return True

    def test_settings_endpoints(self):
        """Test settings endpoints"""
        if not self.token:
            self.log_result("Settings Endpoints", False, "No authentication token")
            return False

        # Get settings
        success, response = self.run_request('GET', 'settings', expected_status=200)
        self.log_result("Get Settings", success,
                       f"Grace period: {response.get('grace_period_minutes', 'Unknown') if success else 'Unknown'}")

        # Test SMS status
        success, response = self.run_request('GET', 'sms/status', expected_status=200)
        sms_enabled = response.get('sms_enabled', False) if success else False
        self.log_result("SMS Status", success,
                       f"SMS enabled: {sms_enabled} (mocked)")

        return True

    def run_all_tests(self):
        """Run all API tests"""
        print("=" * 60)
        print("SMARTATTENDANCE AI - BACKEND API TESTING")
        print("=" * 60)
        print(f"Testing against: {self.base_url}")
        print(f"Started at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print()

        tests = [
            ("Root Endpoint", self.test_root_endpoint),
            ("User Registration", self.test_user_registration),
            ("Authentication", self.test_authentication),
            ("Class Management", self.test_class_management),
            ("User Management", self.test_user_management),
            ("Face Detection", self.test_face_detection),
            ("Attendance Endpoints", self.test_attendance_endpoints),
            ("Analytics Endpoints", self.test_analytics_endpoints),
            ("Settings Endpoints", self.test_settings_endpoints),
        ]

        for test_name, test_func in tests:
            print(f"--- {test_name} ---")
            try:
                test_func()
            except Exception as e:
                self.log_result(f"{test_name} (Exception)", False, f"Error: {str(e)}")
            print()

        print("=" * 60)
        print("BACKEND API TEST SUMMARY")
        print("=" * 60)
        print(f"Total Tests: {self.tests_run}")
        print(f"Passed: {self.tests_passed}")
        print(f"Failed: {self.tests_run - self.tests_passed}")
        print(f"Success Rate: {round((self.tests_passed / self.tests_run * 100) if self.tests_run > 0 else 0, 1)}%")
        print(f"Completed at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        
        return self.tests_passed == self.tests_run

def main():
    tester = SmartAttendanceAPITester()
    success = tester.run_all_tests()
    return 0 if success else 1

if __name__ == "__main__":
    sys.exit(main())