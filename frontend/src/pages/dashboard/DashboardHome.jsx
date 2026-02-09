import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { useTheme } from '@/context/ThemeContext';
import { analyticsAPI, dashboardAPI, classesAPI } from '@/context/AuthContext';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { 
  Users, 
  BookOpen, 
  Calendar, 
  TrendingUp, 
  Camera,
  Clock,
  CheckCircle,
  AlertCircle,
  ArrowRight,
  UserCheck
} from 'lucide-react';
import { 
  LineChart, 
  Line, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell
} from 'recharts';

const COLORS = ['#4338CA', '#D97706', '#BE123C'];

export default function DashboardHome() {
  const { user } = useAuth();
  const { isDark } = useTheme();
  const [stats, setStats] = useState(null);
  const [overview, setOverview] = useState(null);
  const [classes, setClasses] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      const [statsRes, classesRes] = await Promise.all([
        dashboardAPI.getStats(),
        classesAPI.getAll()
      ]);
      setStats(statsRes.data);
      setClasses(classesRes.data.slice(0, 4));

      if (user?.role === 'admin') {
        const overviewRes = await analyticsAPI.getOverview();
        setOverview(overviewRes.data);
      }
    } catch (error) {
      console.error('Error loading dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  const pieData = overview?.today ? [
    { name: 'Present', value: overview.today.present || 0 },
    { name: 'Late', value: overview.today.late || 0 },
    { name: 'Absent', value: overview.today.absent || 0 }
  ] : [];

  return (
    <div className="space-y-8" data-testid="dashboard-home">
      {/* Welcome Section */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className={cn(
            "text-2xl sm:text-3xl font-bold",
            isDark ? "text-white" : "text-slate-900"
          )}>
            Welcome back, {user?.name?.split(' ')[0]}!
          </h1>
          <p className={cn("mt-1", isDark ? "text-slate-400" : "text-slate-600")}>
            Here's what's happening with your attendance today.
          </p>
        </div>
        
        {(user?.role === 'admin' || user?.role === 'teacher') && (
          <Link to="/dashboard/scanner">
            <Button className="bg-indigo-700 hover:bg-indigo-800" data-testid="quick-scan-btn">
              <Camera className="w-4 h-4 mr-2" />
              Quick Scan
            </Button>
          </Link>
        )}
      </div>

      {/* Stats Cards */}
      {user?.role === 'admin' && overview && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Total Students</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{overview.total_students}</p>
                </div>
                <div className="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                  <Users className="w-6 h-6 text-indigo-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Total Classes</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{overview.total_classes}</p>
                </div>
                <div className="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
                  <BookOpen className="w-6 h-6 text-teal-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Attendance Rate</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{overview.attendance_rate}%</p>
                </div>
                <div className="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                  <TrendingUp className="w-6 h-6 text-amber-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Faces Registered</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{overview.faces_registered}</p>
                </div>
                <div className="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center">
                  <UserCheck className="w-6 h-6 text-violet-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Teacher Stats */}
      {user?.role === 'teacher' && stats && (
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">My Classes</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{stats.total_classes}</p>
                </div>
                <div className="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                  <BookOpen className="w-6 h-6 text-indigo-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Total Students</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{stats.total_students}</p>
                </div>
                <div className="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
                  <Users className="w-6 h-6 text-teal-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Today's Attendance</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{stats.today_attendance}</p>
                </div>
                <div className="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                  <Calendar className="w-6 h-6 text-amber-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Student Stats */}
      {user?.role === 'student' && stats && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Enrolled Classes</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{stats.enrolled_classes}</p>
                </div>
                <div className="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                  <BookOpen className="w-6 h-6 text-indigo-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Attendance Rate</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{stats.attendance_rate}%</p>
                </div>
                <div className="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
                  <TrendingUp className="w-6 h-6 text-teal-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Days Present</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{stats.total_present}</p>
                </div>
                <div className="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                  <CheckCircle className="w-6 h-6 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="card-stats">
            <CardContent className="p-0">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Days Late</p>
                  <p className="text-3xl font-bold text-slate-900 mt-1">{stats.total_late}</p>
                </div>
                <div className="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                  <Clock className="w-6 h-6 text-amber-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Charts Row - Admin Only */}
      {user?.role === 'admin' && overview && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Weekly Trend Chart */}
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Weekly Attendance Trend</CardTitle>
              <CardDescription>Number of students marked present each day</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={overview.weekly_trend}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" />
                    <XAxis dataKey="day" stroke="#64748B" fontSize={12} />
                    <YAxis stroke="#64748B" fontSize={12} />
                    <Tooltip 
                      contentStyle={{ 
                        background: 'white', 
                        border: '1px solid #E2E8F0',
                        borderRadius: '8px'
                      }} 
                    />
                    <Line 
                      type="monotone" 
                      dataKey="count" 
                      stroke="#4338CA" 
                      strokeWidth={3}
                      dot={{ fill: '#4338CA', strokeWidth: 2 }}
                    />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>

          {/* Today's Status Pie */}
          <Card>
            <CardHeader>
              <CardTitle>Today's Status</CardTitle>
              <CardDescription>Attendance breakdown for today</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="h-64 flex items-center justify-center">
                {pieData.some(d => d.value > 0) ? (
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={pieData}
                        cx="50%"
                        cy="50%"
                        innerRadius={50}
                        outerRadius={80}
                        paddingAngle={5}
                        dataKey="value"
                      >
                        {pieData.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                        ))}
                      </Pie>
                      <Tooltip />
                    </PieChart>
                  </ResponsiveContainer>
                ) : (
                  <div className="text-center text-slate-500">
                    <Calendar className="w-12 h-12 mx-auto mb-2 text-slate-300" />
                    <p>No attendance data yet</p>
                  </div>
                )}
              </div>
              <div className="flex justify-center gap-4 mt-4">
                {pieData.map((item, idx) => (
                  <div key={item.name} className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full" style={{ backgroundColor: COLORS[idx] }} />
                    <span className="text-sm text-slate-600">{item.name}: {item.value}</span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Recent Classes */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>Your Classes</CardTitle>
            <CardDescription>Quick access to your enrolled classes</CardDescription>
          </div>
          <Link to="/dashboard/classes">
            <Button variant="outline" size="sm">
              View All
              <ArrowRight className="w-4 h-4 ml-2" />
            </Button>
          </Link>
        </CardHeader>
        <CardContent>
          {classes.length > 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {classes.map((cls) => (
                <Link 
                  key={cls.id} 
                  to={`/dashboard/classes/${cls.id}`}
                  className="block"
                >
                  <div className="p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:shadow-md transition-all">
                    <div className="flex items-start justify-between mb-3">
                      <div>
                        <h3 className="font-semibold text-slate-900">{cls.name}</h3>
                        <p className="text-sm text-slate-500">{cls.subject}</p>
                      </div>
                      <Badge variant="secondary">{cls.student_count} students</Badge>
                    </div>
                    {cls.schedule && (
                      <div className="flex items-center gap-2 text-sm text-slate-500">
                        <Clock className="w-4 h-4" />
                        <span>{cls.schedule.day} • {cls.schedule.start} - {cls.schedule.end}</span>
                      </div>
                    )}
                  </div>
                </Link>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <BookOpen className="w-12 h-12 mx-auto text-slate-300 mb-3" />
              <p className="text-slate-500">No classes found</p>
              {(user?.role === 'admin' || user?.role === 'teacher') && (
                <Link to="/dashboard/classes">
                  <Button className="mt-4" variant="outline">
                    Create Your First Class
                  </Button>
                </Link>
              )}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Face Registration Reminder for Students */}
      {user?.role === 'student' && !user?.face_registered && (
        <Card className="border-amber-200 bg-amber-50">
          <CardContent className="p-6">
            <div className="flex items-start gap-4">
              <div className="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <AlertCircle className="w-6 h-6 text-amber-600" />
              </div>
              <div className="flex-1">
                <h3 className="font-semibold text-slate-900 mb-1">Face Registration Required</h3>
                <p className="text-slate-600 text-sm mb-4">
                  You need to register your face to use the automated attendance system. 
                  This only takes a minute.
                </p>
                <Link to="/dashboard/face-registration">
                  <Button className="bg-amber-600 hover:bg-amber-700">
                    <Camera className="w-4 h-4 mr-2" />
                    Register Now
                  </Button>
                </Link>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
