import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { classesAPI, usersAPI, attendanceAPI } from '@/context/AuthContext';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';
import { 
  ArrowLeft,
  Users, 
  Clock, 
  Camera,
  UserPlus,
  UserMinus,
  CheckCircle,
  XCircle,
  AlertCircle,
  Search,
  Loader2
} from 'lucide-react';

export default function ClassDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [classData, setClassData] = useState(null);
  const [students, setStudents] = useState([]);
  const [allStudents, setAllStudents] = useState([]);
  const [todayAttendance, setTodayAttendance] = useState([]);
  const [loading, setLoading] = useState(true);
  const [enrollDialogOpen, setEnrollDialogOpen] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState('');
  const [enrolling, setEnrolling] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    loadClassData();
  }, [id]);

  const loadClassData = async () => {
    try {
      const [classRes, studentsRes, attendanceRes] = await Promise.all([
        classesAPI.getById(id),
        classesAPI.getStudents(id),
        attendanceAPI.getToday(id)
      ]);
      
      setClassData(classRes.data);
      setStudents(studentsRes.data);
      setTodayAttendance(attendanceRes.data);

      // Load all students for enrollment dialog
      if (user?.role === 'admin' || user?.role === 'teacher') {
        const allStudentsRes = await usersAPI.getAll({ role: 'student' });
        // Filter out already enrolled students
        const enrolledIds = studentsRes.data.map(s => s.id);
        setAllStudents(allStudentsRes.data.filter(s => !enrolledIds.includes(s.id)));
      }
    } catch (error) {
      toast.error('Failed to load class data');
      navigate('/dashboard/classes');
    } finally {
      setLoading(false);
    }
  };

  const handleEnrollStudent = async () => {
    if (!selectedStudent) return;
    setEnrolling(true);

    try {
      await classesAPI.enrollStudent(id, selectedStudent);
      toast.success('Student enrolled successfully');
      setEnrollDialogOpen(false);
      setSelectedStudent('');
      loadClassData();
    } catch (error) {
      toast.error(error.response?.data?.detail || 'Failed to enroll student');
    } finally {
      setEnrolling(false);
    }
  };

  const handleUnenrollStudent = async (studentId) => {
    if (!window.confirm('Remove this student from the class?')) return;

    try {
      await classesAPI.unenrollStudent(id, studentId);
      toast.success('Student removed from class');
      loadClassData();
    } catch (error) {
      toast.error('Failed to remove student');
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'present':
        return <Badge className="bg-teal-100 text-teal-700">Present</Badge>;
      case 'late':
        return <Badge className="bg-amber-100 text-amber-700">Late</Badge>;
      case 'absent':
        return <Badge className="bg-red-100 text-red-700">Absent</Badge>;
      default:
        return <Badge variant="secondary">Not Marked</Badge>;
    }
  };

  const canManage = user?.role === 'admin' || user?.role === 'teacher';

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!classData) return null;

  const filteredStudents = students.filter(s => 
    s.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    s.email.toLowerCase().includes(searchQuery.toLowerCase())
  );

  // Merge student data with attendance
  const studentsWithAttendance = filteredStudents.map(student => {
    const attendance = todayAttendance.find(a => a.student_id === student.id);
    return {
      ...student,
      todayStatus: attendance?.status || 'not_marked',
      todayTime: attendance?.timestamp
    };
  });

  return (
    <div className="space-y-6" data-testid="class-detail-page">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Button 
          variant="ghost" 
          size="icon"
          onClick={() => navigate('/dashboard/classes')}
        >
          <ArrowLeft className="w-5 h-5" />
        </Button>
        <div className="flex-1">
          <h1 className="text-2xl font-bold text-slate-900">{classData.name}</h1>
          <p className="text-slate-600">{classData.subject}</p>
        </div>
        {canManage && (
          <Button 
            className="bg-indigo-700 hover:bg-indigo-800"
            onClick={() => navigate(`/dashboard/scanner?class=${id}`)}
          >
            <Camera className="w-4 h-4 mr-2" />
            Take Attendance
          </Button>
        )}
      </div>

      {/* Class Info Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Card>
          <CardContent className="p-4 flex items-center gap-4">
            <div className="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
              <Users className="w-6 h-6 text-indigo-600" />
            </div>
            <div>
              <p className="text-sm text-slate-500">Students</p>
              <p className="text-2xl font-bold text-slate-900">{classData.student_count}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4 flex items-center gap-4">
            <div className="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
              <Clock className="w-6 h-6 text-teal-600" />
            </div>
            <div>
              <p className="text-sm text-slate-500">Schedule</p>
              <p className="text-lg font-semibold text-slate-900">
                {classData.schedule ? `${classData.schedule.day} ${classData.schedule.start}` : 'Not set'}
              </p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4 flex items-center gap-4">
            <div className="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
              <AlertCircle className="w-6 h-6 text-amber-600" />
            </div>
            <div>
              <p className="text-sm text-slate-500">Grace Period</p>
              <p className="text-lg font-semibold text-slate-900">{classData.grace_period_minutes} min</p>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Today's Attendance Summary */}
      <Card>
        <CardHeader>
          <CardTitle>Today's Attendance</CardTitle>
          <CardDescription>Quick overview of today's class</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex gap-6">
            <div className="flex items-center gap-2">
              <CheckCircle className="w-5 h-5 text-teal-600" />
              <span className="text-slate-600">
                Present: {todayAttendance.filter(a => a.status === 'present').length}
              </span>
            </div>
            <div className="flex items-center gap-2">
              <AlertCircle className="w-5 h-5 text-amber-600" />
              <span className="text-slate-600">
                Late: {todayAttendance.filter(a => a.status === 'late').length}
              </span>
            </div>
            <div className="flex items-center gap-2">
              <XCircle className="w-5 h-5 text-red-600" />
              <span className="text-slate-600">
                Not Marked: {students.length - todayAttendance.length}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Students List */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>Enrolled Students</CardTitle>
            <CardDescription>Students in this class</CardDescription>
          </div>
          {canManage && (
            <Dialog open={enrollDialogOpen} onOpenChange={setEnrollDialogOpen}>
              <DialogTrigger asChild>
                <Button data-testid="enroll-student-btn">
                  <UserPlus className="w-4 h-4 mr-2" />
                  Enroll Student
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Enroll Student</DialogTitle>
                  <DialogDescription>
                    Add a student to this class
                  </DialogDescription>
                </DialogHeader>
                <div className="space-y-4 mt-4">
                  <Select value={selectedStudent} onValueChange={setSelectedStudent}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select a student" />
                    </SelectTrigger>
                    <SelectContent>
                      {allStudents.map(student => (
                        <SelectItem key={student.id} value={student.id}>
                          {student.name} ({student.email})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>

                  {allStudents.length === 0 && (
                    <p className="text-sm text-slate-500 text-center py-4">
                      All students are already enrolled in this class
                    </p>
                  )}

                  <div className="flex justify-end gap-3">
                    <Button variant="outline" onClick={() => setEnrollDialogOpen(false)}>
                      Cancel
                    </Button>
                    <Button 
                      onClick={handleEnrollStudent}
                      disabled={!selectedStudent || enrolling}
                    >
                      {enrolling ? (
                        <>
                          <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                          Enrolling...
                        </>
                      ) : (
                        'Enroll'
                      )}
                    </Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          )}
        </CardHeader>
        <CardContent>
          {/* Search */}
          <div className="relative mb-4 max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <Input
              placeholder="Search students..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-9"
            />
          </div>

          {studentsWithAttendance.length > 0 ? (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Face Registered</TableHead>
                  <TableHead>Today's Status</TableHead>
                  {canManage && <TableHead className="text-right">Actions</TableHead>}
                </TableRow>
              </TableHeader>
              <TableBody>
                {studentsWithAttendance.map((student) => (
                  <TableRow key={student.id}>
                    <TableCell className="font-medium">{student.name}</TableCell>
                    <TableCell>{student.email}</TableCell>
                    <TableCell>
                      {student.face_registered ? (
                        <Badge className="bg-teal-100 text-teal-700">Yes</Badge>
                      ) : (
                        <Badge variant="secondary">No</Badge>
                      )}
                    </TableCell>
                    <TableCell>{getStatusBadge(student.todayStatus)}</TableCell>
                    {canManage && (
                      <TableCell className="text-right">
                        <Button
                          variant="ghost"
                          size="sm"
                          className="text-red-600 hover:text-red-700 hover:bg-red-50"
                          onClick={() => handleUnenrollStudent(student.id)}
                        >
                          <UserMinus className="w-4 h-4" />
                        </Button>
                      </TableCell>
                    )}
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          ) : (
            <div className="text-center py-8">
              <Users className="w-12 h-12 mx-auto text-slate-300 mb-3" />
              <p className="text-slate-500">No students enrolled yet</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
