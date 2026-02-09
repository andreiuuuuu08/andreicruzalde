import React, { useState, useRef, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { faceAPI } from '@/context/AuthContext';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { toast } from 'sonner';
import { 
  Camera, 
  CameraOff,
  CheckCircle,
  AlertCircle,
  ArrowRight,
  RefreshCcw,
  Loader2,
  X
} from 'lucide-react';

const REQUIRED_PHOTOS = 3;

export default function FaceRegistrationPage() {
  const navigate = useNavigate();
  const { user, updateUser } = useAuth();
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const streamRef = useRef(null);
  
  const [cameraActive, setCameraActive] = useState(false);
  const [capturedPhotos, setCapturedPhotos] = useState([]);
  const [registering, setRegistering] = useState(false);
  const [step, setStep] = useState(1); // 1: intro, 2: capture, 3: complete

  const startCamera = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ 
        video: { 
          width: { ideal: 640 },
          height: { ideal: 480 },
          facingMode: 'user'
        } 
      });
      
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        streamRef.current = stream;
        setCameraActive(true);
        setStep(2);
      }
    } catch (error) {
      console.error('Camera error:', error);
      toast.error('Failed to access camera. Please check permissions.');
    }
  };

  const stopCamera = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach(track => track.stop());
      streamRef.current = null;
    }
    if (videoRef.current) {
      videoRef.current.srcObject = null;
    }
    setCameraActive(false);
  };

  const capturePhoto = useCallback(() => {
    if (!videoRef.current || !canvasRef.current) return;
    
    const video = videoRef.current;
    const canvas = canvasRef.current;
    const context = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    
    const imageData = canvas.toDataURL('image/jpeg', 0.8);
    
    setCapturedPhotos(prev => {
      const newPhotos = [...prev, imageData];
      if (newPhotos.length >= REQUIRED_PHOTOS) {
        stopCamera();
      }
      return newPhotos;
    });
    
    toast.success(`Photo ${capturedPhotos.length + 1} captured!`);
  }, [capturedPhotos.length]);

  const removePhoto = (index) => {
    setCapturedPhotos(prev => prev.filter((_, i) => i !== index));
  };

  const handleRegister = async () => {
    if (capturedPhotos.length < REQUIRED_PHOTOS) {
      toast.error(`Please capture at least ${REQUIRED_PHOTOS} photos`);
      return;
    }

    setRegistering(true);

    try {
      await faceAPI.register(user.id, capturedPhotos);
      updateUser({ face_registered: true });
      setStep(3);
      toast.success('Face registered successfully!');
    } catch (error) {
      const message = error.response?.data?.detail || 'Registration failed';
      toast.error(message);
    } finally {
      setRegistering(false);
    }
  };

  const progress = (capturedPhotos.length / REQUIRED_PHOTOS) * 100;

  return (
    <div className="max-w-2xl mx-auto space-y-6" data-testid="face-registration-page">
      {/* Header */}
      <div className="text-center">
        <h1 className="text-2xl font-bold text-slate-900">Face Registration</h1>
        <p className="text-slate-600">Register your face for automated attendance</p>
      </div>

      {/* Step 1: Introduction */}
      {step === 1 && (
        <Card>
          <CardHeader className="text-center">
            <div className="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <Camera className="w-8 h-8 text-indigo-600" />
            </div>
            <CardTitle>Let's Get Started</CardTitle>
            <CardDescription>
              We need to capture a few photos of your face to enable automatic attendance marking.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="bg-slate-50 rounded-xl p-4 space-y-3">
              <h4 className="font-medium text-slate-900">Tips for best results:</h4>
              <ul className="text-sm text-slate-600 space-y-2">
                <li className="flex items-start gap-2">
                  <CheckCircle className="w-4 h-4 text-teal-600 mt-0.5" />
                  <span>Ensure good lighting on your face</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircle className="w-4 h-4 text-teal-600 mt-0.5" />
                  <span>Look directly at the camera</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircle className="w-4 h-4 text-teal-600 mt-0.5" />
                  <span>Remove glasses or accessories if possible</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircle className="w-4 h-4 text-teal-600 mt-0.5" />
                  <span>Keep a neutral expression</span>
                </li>
              </ul>
            </div>

            <Button 
              onClick={startCamera} 
              className="w-full bg-indigo-700 hover:bg-indigo-800"
              data-testid="start-camera-btn"
            >
              Start Camera
              <ArrowRight className="w-4 h-4 ml-2" />
            </Button>
          </CardContent>
        </Card>
      )}

      {/* Step 2: Capture Photos */}
      {step === 2 && (
        <Card>
          <CardHeader>
            <CardTitle>Capture Your Photos</CardTitle>
            <CardDescription>
              Take {REQUIRED_PHOTOS} photos from slightly different angles
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Camera View */}
            <div className="relative aspect-video bg-slate-900 rounded-xl overflow-hidden">
              <video
                ref={videoRef}
                autoPlay
                playsInline
                muted
                className="w-full h-full object-cover"
              />
              <canvas ref={canvasRef} className="hidden" />

              {/* Viewfinder overlay */}
              {cameraActive && (
                <div className="absolute inset-0 pointer-events-none">
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="w-48 h-48 border-2 border-white/50 rounded-full" />
                  </div>
                  <div className="absolute bottom-4 left-0 right-0 text-center">
                    <p className="text-white text-sm bg-black/50 inline-block px-3 py-1 rounded-full">
                      Position your face within the circle
                    </p>
                  </div>
                </div>
              )}

              {!cameraActive && capturedPhotos.length >= REQUIRED_PHOTOS && (
                <div className="absolute inset-0 flex items-center justify-center bg-slate-900/80">
                  <div className="text-center">
                    <CheckCircle className="w-16 h-16 text-teal-500 mx-auto mb-4" />
                    <p className="text-white text-lg font-medium">All photos captured!</p>
                  </div>
                </div>
              )}
            </div>

            {/* Progress */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="text-slate-600">Progress</span>
                <span className="font-medium text-slate-900">
                  {capturedPhotos.length} / {REQUIRED_PHOTOS} photos
                </span>
              </div>
              <Progress value={progress} className="h-2" />
            </div>

            {/* Captured Photos Preview */}
            {capturedPhotos.length > 0 && (
              <div className="space-y-2">
                <p className="text-sm font-medium text-slate-700">Captured Photos</p>
                <div className="flex gap-3">
                  {capturedPhotos.map((photo, idx) => (
                    <div key={idx} className="relative w-20 h-20 rounded-lg overflow-hidden">
                      <img src={photo} alt={`Capture ${idx + 1}`} className="w-full h-full object-cover" />
                      <button
                        onClick={() => removePhoto(idx)}
                        className="absolute top-1 right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center"
                      >
                        <X className="w-3 h-3 text-white" />
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Actions */}
            <div className="flex gap-3">
              {cameraActive ? (
                <>
                  <Button
                    variant="outline"
                    onClick={stopCamera}
                    className="flex-1"
                  >
                    <CameraOff className="w-4 h-4 mr-2" />
                    Stop
                  </Button>
                  <Button
                    onClick={capturePhoto}
                    disabled={capturedPhotos.length >= REQUIRED_PHOTOS}
                    className="flex-1 bg-teal-600 hover:bg-teal-700"
                    data-testid="capture-btn"
                  >
                    <Camera className="w-4 h-4 mr-2" />
                    Capture ({capturedPhotos.length + 1})
                  </Button>
                </>
              ) : (
                <>
                  {capturedPhotos.length < REQUIRED_PHOTOS ? (
                    <Button
                      onClick={startCamera}
                      className="flex-1"
                    >
                      <RefreshCcw className="w-4 h-4 mr-2" />
                      Continue Capturing
                    </Button>
                  ) : (
                    <Button
                      onClick={handleRegister}
                      disabled={registering}
                      className="flex-1 bg-indigo-700 hover:bg-indigo-800"
                      data-testid="register-face-btn"
                    >
                      {registering ? (
                        <>
                          <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                          Registering...
                        </>
                      ) : (
                        <>
                          <CheckCircle className="w-4 h-4 mr-2" />
                          Register Face
                        </>
                      )}
                    </Button>
                  )}
                </>
              )}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Step 3: Complete */}
      {step === 3 && (
        <Card>
          <CardContent className="py-12 text-center">
            <div className="w-20 h-20 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <CheckCircle className="w-10 h-10 text-teal-600" />
            </div>
            <h2 className="text-2xl font-bold text-slate-900 mb-2">Registration Complete!</h2>
            <p className="text-slate-600 mb-8 max-w-md mx-auto">
              Your face has been registered successfully. You can now use the automated 
              attendance system to mark your attendance.
            </p>
            <Button 
              onClick={() => navigate('/dashboard')}
              className="bg-indigo-700 hover:bg-indigo-800"
            >
              Go to Dashboard
              <ArrowRight className="w-4 h-4 ml-2" />
            </Button>
          </CardContent>
        </Card>
      )}

      {/* Already Registered Notice */}
      {user?.face_registered && step === 1 && (
        <Card className="border-teal-200 bg-teal-50">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <CheckCircle className="w-5 h-5 text-teal-600" />
              <div>
                <p className="font-medium text-teal-900">Face Already Registered</p>
                <p className="text-sm text-teal-700">
                  You can re-register if needed by starting the camera.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
