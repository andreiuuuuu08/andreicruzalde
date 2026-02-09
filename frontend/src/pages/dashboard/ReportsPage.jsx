import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { classesAPI, reportsAPI, analyticsAPI } from '@/context/AuthContext';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { format } from 'date-fns';
import { toast } from 'sonner';
import { 
  FileSpreadsheet, 
  FileText,
  Download,
  CalendarIcon,
  BarChart3,
  TrendingUp,
  Loader2
} from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Legend
} from 'recharts';

const COLORS = ['#4338CA', '#D97706', '#BE123C'];

export default function ReportsPage() {
  const { user } = useAuth();
  const [classes, setClasses] = useState([]);
  const [selectedClass, setSelectedClass] = useState('all');
  const [dateFrom, setDateFrom] = useState(null);
  const [dateTo, setDateTo] = useState(null);
  const [exporting, setExporting] = useState(null);
  const [analytics, setAnalytics] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    if (selectedClass && selectedClass !== 'all') {
      loadClassAnalytics();
    }
  }, [selectedClass]);

  const loadData = async () => {
    try {
      const [classesRes, overviewRes] = await Promise.all([
        classesAPI.getAll(),
        analyticsAPI.getOverview()
      ]);
      setClasses(classesRes.data);
      setAnalytics(overviewRes.data);
    } catch (error) {
      console.error('Failed to load data:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadClassAnalytics = async () => {
    try {
      const response = await analyticsAPI.getClassAnalytics(selectedClass);
      setAnalytics(prev => ({ ...prev, classData: response.data }));
    } catch (error) {
      console.error('Failed to load class analytics:', error);
    }
  };

  const handleExport = async (type) => {
    setExporting(type);

    try {
      const params = {};
      if (selectedClass !== 'all') params.class_id = selectedClass;
      if (dateFrom) params.date_from = format(dateFrom, 'yyyy-MM-dd');
      if (dateTo) params.date_to = format(dateTo, 'yyyy-MM-dd');

      let response;
      let filename;

      if (type === 'excel') {
        response = await reportsAPI.exportExcel(params);
        filename = `attendance_report_${format(new Date(), 'yyyyMMdd')}.xlsx`;
      } else {
        response = await reportsAPI.exportPDF(params);
        filename = `attendance_report_${format(new Date(), 'yyyyMMdd')}.pdf`;
      }

      // Download file
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      link.remove();

      toast.success(`${type.toUpperCase()} report downloaded`);
    } catch (error) {
      toast.error(`Failed to export ${type}`);
    } finally {
      setExporting(null);
    }
  };

  const pieData = analytics?.classData?.summary ? [
    { name: 'Present', value: analytics.classData.summary.present },
    { name: 'Late', value: analytics.classData.summary.late },
    { name: 'Absent', value: analytics.classData.summary.absent }
  ] : analytics?.today ? [
    { name: 'Present', value: analytics.today.present },
    { name: 'Late', value: analytics.today.late },
    { name: 'Absent', value: analytics.today.absent }
  ] : [];

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6" data-testid="reports-page">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Reports & Analytics</h1>
        <p className="text-slate-600">Export reports and view attendance analytics</p>
      </div>

      {/* Export Section */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Download className="w-5 h-5" />
            Export Reports
          </CardTitle>
          <CardDescription>Download attendance data in various formats</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {/* Class Filter */}
            <Select value={selectedClass} onValueChange={setSelectedClass}>
              <SelectTrigger>
                <SelectValue placeholder="All Classes" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Classes</SelectItem>
                {classes.map(cls => (
                  <SelectItem key={cls.id} value={cls.id}>{cls.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>

            {/* Date From */}
            <Popover>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  className={cn(
                    "justify-start text-left font-normal",
                    !dateFrom && "text-muted-foreground"
                  )}
                >
                  <CalendarIcon className="mr-2 h-4 w-4" />
                  {dateFrom ? format(dateFrom, "PPP") : "From date"}
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                  mode="single"
                  selected={dateFrom}
                  onSelect={setDateFrom}
                  initialFocus
                />
              </PopoverContent>
            </Popover>

            {/* Date To */}
            <Popover>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  className={cn(
                    "justify-start text-left font-normal",
                    !dateTo && "text-muted-foreground"
                  )}
                >
                  <CalendarIcon className="mr-2 h-4 w-4" />
                  {dateTo ? format(dateTo, "PPP") : "To date"}
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                  mode="single"
                  selected={dateTo}
                  onSelect={setDateTo}
                  initialFocus
                />
              </PopoverContent>
            </Popover>

            <div className="flex gap-2">
              {(dateFrom || dateTo) && (
                <Button 
                  variant="ghost" 
                  onClick={() => { setDateFrom(null); setDateTo(null); }}
                >
                  Clear
                </Button>
              )}
            </div>
          </div>

          {/* Export Buttons */}
          <div className="flex flex-wrap gap-4">
            <Button
              onClick={() => handleExport('excel')}
              disabled={exporting === 'excel'}
              className="bg-green-600 hover:bg-green-700"
              data-testid="export-excel-btn"
            >
              {exporting === 'excel' ? (
                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
              ) : (
                <FileSpreadsheet className="w-4 h-4 mr-2" />
              )}
              Export to Excel
            </Button>

            <Button
              onClick={() => handleExport('pdf')}
              disabled={exporting === 'pdf'}
              className="bg-red-600 hover:bg-red-700"
              data-testid="export-pdf-btn"
            >
              {exporting === 'pdf' ? (
                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
              ) : (
                <FileText className="w-4 h-4 mr-2" />
              )}
              Export to PDF
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Analytics Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Weekly Trend */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <TrendingUp className="w-5 h-5" />
              Weekly Attendance Trend
            </CardTitle>
          </CardHeader>
          <CardContent>
            {analytics?.weekly_trend ? (
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={analytics.weekly_trend}>
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
                    <Bar dataKey="count" fill="#4338CA" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            ) : (
              <div className="h-64 flex items-center justify-center text-slate-500">
                No data available
              </div>
            )}
          </CardContent>
        </Card>

        {/* Status Distribution */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <BarChart3 className="w-5 h-5" />
              Attendance Distribution
            </CardTitle>
            <CardDescription>
              {selectedClass !== 'all' && analytics?.classData ? 
                `${analytics.classData.class_name} - Last 30 days` : 
                "Today's overview"}
            </CardDescription>
          </CardHeader>
          <CardContent>
            {pieData.some(d => d.value > 0) ? (
              <div className="h-64">
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
                      label={({ name, value }) => `${name}: ${value}`}
                    >
                      {pieData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                      ))}
                    </Pie>
                    <Tooltip />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            ) : (
              <div className="h-64 flex items-center justify-center text-slate-500">
                No attendance data available
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Summary Stats */}
      {analytics && (
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <Card>
            <CardContent className="p-4 text-center">
              <p className="text-3xl font-bold text-indigo-600">{analytics.total_students || 0}</p>
              <p className="text-sm text-slate-500">Total Students</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4 text-center">
              <p className="text-3xl font-bold text-teal-600">{analytics.total_classes || 0}</p>
              <p className="text-sm text-slate-500">Active Classes</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4 text-center">
              <p className="text-3xl font-bold text-amber-600">{analytics.attendance_rate || 0}%</p>
              <p className="text-sm text-slate-500">Attendance Rate</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4 text-center">
              <p className="text-3xl font-bold text-violet-600">{analytics.faces_registered || 0}</p>
              <p className="text-sm text-slate-500">Faces Registered</p>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}
