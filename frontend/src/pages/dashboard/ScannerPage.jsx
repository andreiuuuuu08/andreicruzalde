import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { classesAPI, attendanceAPI } from '@/context/AuthContext';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { toast } from 'sonner';
import { 
  Camera, 
  CameraOff,
  Scan,
  CheckCircle,
  Clock,
  AlertCircle,
  Users,
  RefreshCcw,
  Volume2
} from 'lucide-react';

export default function ScannerPage() {
  const { user } = useAuth();
  const [searchParams] = useSearchParams();
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const [classes, setClasses] = useState([]);
  const [selectedClass, setSelectedClass] = useState(searchParams.get('class') || '');
  const [cameraActive, setCameraActive] = useState(false);
  const [scanning, setScanning] = useState(false);
  const [recentScans, setRecentScans] = useState([]);
  const [todayStats, setTodayStats] = useState({ present: 0, late: 0, total: 0 });
  const [lastScanResult, setLastScanResult] = useState(null);
  const streamRef = useRef(null);

  useEffect(() => {
    loadClasses();
    return () => stopCamera();
  }, []);

  useEffect(() => {
    if (selectedClass) {
      loadTodayStats();
    }
  }, [selectedClass]);

  const loadClasses = async () => {
    try {
      const response = await classesAPI.getAll();
      setClasses(response.data);
      if (response.data.length > 0 && !selectedClass) {
        setSelectedClass(response.data[0].id);
      }
    } catch (error) {
      toast.error('Failed to load classes');
    }
  };

  const loadTodayStats = async () => {
    try {
      const response = await attendanceAPI.getToday(selectedClass);
      const data = response.data;
      setTodayStats({
        present: data.filter(a => a.status === 'present').length,
        late: data.filter(a => a.status === 'late').length,
        total: data.length
      });
    } catch (error) {
      console.error('Failed to load today stats:', error);
    }
  };

  const startCamera = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ 
        video: { 
          width: { ideal: 1280 },
          height: { ideal: 720 },
          facingMode: 'user'
        } 
      });
      
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        streamRef.current = stream;
        setCameraActive(true);
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

  const captureFrame = useCallback(() => {
    if (!videoRef.current || !canvasRef.current) return null;
    
    const video = videoRef.current;
    const canvas = canvasRef.current;
    const context = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    
    return canvas.toDataURL('image/jpeg', 0.8);
  }, []);

  const handleScan = async () => {
    if (!selectedClass) {
      toast.error('Please select a class first');
      return;
    }

    if (!cameraActive) {
      toast.error('Please start the camera first');
      return;
    }

    setScanning(true);
    setLastScanResult(null);

    try {
      const imageData = captureFrame();
      if (!imageData) {
        throw new Error('Failed to capture image');
      }

      const response = await attendanceAPI.mark({
        class_id: selectedClass,
        face_image: imageData
      });

      const result = response.data;
      
      // Play success sound
      playSound(result.status === 'present' ? 'success' : 'warning');
      
      setLastScanResult(result);
      setRecentScans(prev => [result, ...prev.slice(0, 9)]);
      
      toast.success(`${result.student_name} marked as ${result.status.toUpperCase()}`);
      
      // Refresh stats
      loadTodayStats();

    } catch (error) {
      const message = error.response?.data?.detail || 'Scan failed';
      toast.error(message);
      playSound('error');
      setLastScanResult({ error: true, message });
    } finally {
      setScanning(false);
    }
  };

  const playSound = (type) => {
    // Create audio context for feedback sounds
    try {
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();
      
      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);
      
      if (type === 'success') {
        oscillator.frequency.value = 800;
        gainNode.gain.value = 0.1;
      } else if (type === 'warning') {
        oscillator.frequency.value = 600;
        gainNode.gain.value = 0.1;
      } else {
        oscillator.frequency.value = 300;
        gainNode.gain.value = 0.1;
      }
      
      oscillator.start();
      oscillator.stop(audioContext.currentTime + 0.15);
    } catch (e) {
      // Audio not supported
    }
  };

  const selectedClassData = classes.find(c => c.id === selectedClass);

  return (
    <div className="space-y-6" data-testid="scanner-page">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Attendance Scanner</h1>
          <p className="text-slate-600">Mark attendance using face recognition</p>
        </div>

        <Select value={selectedClass} onValueChange={setSelectedClass}>
          <SelectTrigger className="w-64" data-testid="scanner-class-select">
            <SelectValue placeholder="Select a class" />
          </SelectTrigger>
          <SelectContent>
            {classes.map(cls => (
              <SelectItem key={cls.id} value={cls.id}>
                {cls.name} - {cls.subject}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Stats */}
      {selectedClass && (
        <div className="grid grid-cols-3 gap-4">
          <Card>
            <CardContent className="p-4 flex items-center gap-3">
              <div className="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                <CheckCircle className="w-5 h-5 text-teal-600" />
              </div>
              <div>
                <p className="text-sm text-slate-500">Present</p>
                <p className="text-xl font-bold text-slate-900">{todayStats.present}</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4 flex items-center gap-3">
              <div className="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                <Clock className="w-5 h-5 text-amber-600" />
              </div>
              <div>
                <p className="text-sm text-slate-500">Late</p>
                <p className="text-xl font-bold text-slate-900">{todayStats.late}</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4 flex items-center gap-3">
              <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                <Users className="w-5 h-5 text-indigo-600" />
              </div>
              <div>
                <p className="text-sm text-slate-500">Total Today</p>
                <p className="text-xl font-bold text-slate-900">{todayStats.total}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Scanner Interface */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Camera View */}
        <div className="lg:col-span-2">
          <Card className="overflow-hidden">
            <div className="relative aspect-video bg-slate-900">
              {/* Video Feed */}
              <video
                ref={videoRef}
                autoPlay
                playsInline
                muted
                className="w-full h-full object-cover"
              />
              <canvas ref={canvasRef} className="hidden" />
              
              {/* Scanner Overlay */}
              {cameraActive && (
                <div className="absolute inset-0 pointer-events-none">
                  {/* Grid overlay */}
                  <div className="absolute inset-0 scanner-grid opacity-30" />
                  
                  {/* Viewfinder */}
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="w-64 h-64 viewfinder relative">
                      {scanning && (
                        <div className="absolute inset-0 flex items-center justify-center">
                          <div className="w-full h-1 bg-gradient-to-r from-transparent via-teal-400 to-transparent animate-scan" />
                        </div>
                      )}
                    </div>
                  </div>
                  
                  {/* Status indicator */}
                  <div className="absolute top-4 left-4 flex items-center gap-2">
                    <div className={`w-3 h-3 rounded-full ${scanning ? 'bg-amber-500 animate-pulse' : 'bg-teal-500'}`} />
                    <span className="text-white text-sm font-mono">
                      {scanning ? 'SCANNING...' : 'READY'}
                    </span>
                  </div>

                  {/* Class info */}
                  {selectedClassData && (
                    <div className="absolute top-4 right-4 bg-black/50 backdrop-blur px-3 py-2 rounded-lg">
                      <p className="text-white text-sm font-medium">{selectedClassData.name}</p>
                      <p className="text-white/70 text-xs">{selectedClassData.schedule?.start || 'No schedule'}</p>
                    </div>
                  )}
                </div>
              )}
              
              {/* No Camera Placeholder */}
              {!cameraActive && (
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                  <CameraOff className="w-16 h-16 text-slate-500 mb-4" />
                  <p className="text-slate-400">Camera is off</p>
                </div>
              )}

              {/* Last Scan Result */}
              {lastScanResult && !lastScanResult.error && (
                <div className="absolute bottom-4 left-4 right-4 bg-black/70 backdrop-blur-lg rounded-xl p-4 animate-fade-in-up">
                  <div className="flex items-center gap-4">
                    {lastScanResult.status === 'present' ? (
                      <div className="w-12 h-12 bg-teal-500 rounded-full flex items-center justify-center">
                        <CheckCircle className="w-7 h-7 text-white" />
                      </div>
                    ) : (
                      <div className="w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center">
                        <Clock className="w-7 h-7 text-white" />
                      </div>
                    )}
                    <div>
                      <p className="text-white font-semibold text-lg">{lastScanResult.student_name}</p>
                      <p className="text-white/70 text-sm">
                        Marked as <span className="uppercase font-medium">{lastScanResult.status}</span>
                      </p>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* Controls */}
            <CardContent className="p-4 bg-slate-50 border-t">
              <div className="flex flex-wrap items-center justify-center gap-4">
                <Button
                  variant={cameraActive ? "destructive" : "default"}
                  onClick={cameraActive ? stopCamera : startCamera}
                  className="min-w-36"
                  data-testid="camera-toggle-btn"
                >
                  {cameraActive ? (
                    <>
                      <CameraOff className="w-4 h-4 mr-2" />
                      Stop Camera
                    </>
                  ) : (
                    <>
                      <Camera className="w-4 h-4 mr-2" />
                      Start Camera
                    </>
                  )}
                </Button>

                <Button
                  onClick={handleScan}
                  disabled={!cameraActive || scanning || !selectedClass}
                  className="min-w-36 bg-teal-600 hover:bg-teal-700"
                  data-testid="scan-btn"
                >
                  {scanning ? (
                    <>
                      <RefreshCcw className="w-4 h-4 mr-2 animate-spin" />
                      Scanning...
                    </>
                  ) : (
                    <>
                      <Scan className="w-4 h-4 mr-2" />
                      Scan Face
                    </>
                  )}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Recent Scans */}
        <div>
          <Card className="h-full">
            <CardHeader>
              <CardTitle>Recent Scans</CardTitle>
              <CardDescription>Latest attendance marks</CardDescription>
            </CardHeader>
            <CardContent>
              {recentScans.length > 0 ? (
                <div className="space-y-3">
                  {recentScans.map((scan, idx) => (
                    <div 
                      key={`${scan.id}-${idx}`}
                      className="flex items-center gap-3 p-3 rounded-lg bg-slate-50"
                    >
                      <div className={`w-10 h-10 rounded-full flex items-center justify-center ${
                        scan.status === 'present' ? 'bg-teal-100' : 'bg-amber-100'
                      }`}>
                        {scan.status === 'present' ? (
                          <CheckCircle className="w-5 h-5 text-teal-600" />
                        ) : (
                          <Clock className="w-5 h-5 text-amber-600" />
                        )}
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="font-medium text-slate-900 truncate">{scan.student_name}</p>
                        <p className="text-xs text-slate-500">
                          {new Date(scan.timestamp).toLocaleTimeString()}
                        </p>
                      </div>
                      <Badge className={scan.status === 'present' ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700'}>
                        {scan.status}
                      </Badge>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8">
                  <Scan className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                  <p className="text-slate-500 text-sm">No scans yet</p>
                  <p className="text-slate-400 text-xs mt-1">Start scanning to see results</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Instructions */}
      <Card>
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-indigo-600 mt-0.5" />
            <div>
              <p className="font-medium text-slate-900">Scanner Tips</p>
              <ul className="text-sm text-slate-600 mt-1 space-y-1">
                <li>• Ensure good lighting on the student's face</li>
                <li>• Student should face the camera directly</li>
                <li>• Keep a distance of about 1-2 feet from the camera</li>
                <li>• Students must have their face registered before scanning</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
