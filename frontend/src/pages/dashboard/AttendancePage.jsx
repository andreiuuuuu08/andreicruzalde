import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { attendanceAPI, classesAPI } from '@/context/AuthContext';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Calendar } from '@/components/ui/calendar';
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
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { format } from 'date-fns';
import { toast } from 'sonner';
import { 
  CalendarIcon, 
  Search,
  Filter,
  CheckCircle,
  Clock,
  XCircle,
  Image as ImageIcon
} from 'lucide-react';
import { cn } from '@/lib/utils';

export default function AttendancePage() {
  const { user } = useAuth();
  const [attendance, setAttendance] = useState([]);
  const [classes, setClasses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedClass, setSelectedClass] = useState('all');
  const [selectedStatus, setSelectedStatus] = useState('all');
  const [dateFrom, setDateFrom] = useState(null);
  const [dateTo, setDateTo] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    loadAttendance();
  }, [selectedClass, selectedStatus, dateFrom, dateTo]);

  const loadData = async () => {
    try {
      const classesRes = await classesAPI.getAll();
      setClasses(classesRes.data);
    } catch (error) {
      console.error('Error loading classes:', error);
    }
  };

  const loadAttendance = async () => {
    setLoading(true);
    try {
      const params = {};
      if (selectedClass !== 'all') params.class_id = selectedClass;
      if (selectedStatus !== 'all') params.status = selectedStatus;
      if (dateFrom) params.date_from = format(dateFrom, 'yyyy-MM-dd');
      if (dateTo) params.date_to = format(dateTo, 'yyyy-MM-dd');

      const response = await attendanceAPI.getAll(params);
      setAttendance(response.data);
    } catch (error) {
      toast.error('Failed to load attendance records');
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'present':
        return (
          <Badge className="bg-teal-100 text-teal-700">
            <CheckCircle className="w-3 h-3 mr-1" />
            Present
          </Badge>
        );
      case 'late':
        return (
          <Badge className="bg-amber-100 text-amber-700">
            <Clock className="w-3 h-3 mr-1" />
            Late
          </Badge>
        );
      case 'absent':
        return (
          <Badge className="bg-red-100 text-red-700">
            <XCircle className="w-3 h-3 mr-1" />
            Absent
          </Badge>
        );
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const filteredAttendance = attendance.filter(a => 
    a.student_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    a.class_name.toLowerCase().includes(searchQuery.toLowerCase())
  );

  // Stats
  const presentCount = attendance.filter(a => a.status === 'present').length;
  const lateCount = attendance.filter(a => a.status === 'late').length;
  const absentCount = attendance.filter(a => a.status === 'absent').length;

  return (
    <div className="space-y-6" data-testid="attendance-page">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Attendance Records</h1>
        <p className="text-slate-600">View and filter attendance history</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Card>
          <CardContent className="p-4 flex items-center gap-4">
            <div className="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
              <CheckCircle className="w-6 h-6 text-teal-600" />
            </div>
            <div>
              <p className="text-sm text-slate-500">Present</p>
              <p className="text-2xl font-bold text-slate-900">{presentCount}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4 flex items-center gap-4">
            <div className="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
              <Clock className="w-6 h-6 text-amber-600" />
            </div>
            <div>
              <p className="text-sm text-slate-500">Late</p>
              <p className="text-2xl font-bold text-slate-900">{lateCount}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4 flex items-center gap-4">
            <div className="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
              <XCircle className="w-6 h-6 text-red-600" />
            </div>
            <div>
              <p className="text-sm text-slate-500">Absent</p>
              <p className="text-2xl font-bold text-slate-900">{absentCount}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="w-5 h-5" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {/* Search */}
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <Input
                placeholder="Search..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-9"
              />
            </div>

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

            {/* Status Filter */}
            <Select value={selectedStatus} onValueChange={setSelectedStatus}>
              <SelectTrigger>
                <SelectValue placeholder="All Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="present">Present</SelectItem>
                <SelectItem value="late">Late</SelectItem>
                <SelectItem value="absent">Absent</SelectItem>
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
          </div>

          {(dateFrom || dateTo || selectedClass !== 'all' || selectedStatus !== 'all') && (
            <Button 
              variant="ghost" 
              className="mt-4"
              onClick={() => {
                setDateFrom(null);
                setDateTo(null);
                setSelectedClass('all');
                setSelectedStatus('all');
              }}
            >
              Clear Filters
            </Button>
          )}
        </CardContent>
      </Card>

      {/* Attendance Table */}
      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin" />
            </div>
          ) : filteredAttendance.length > 0 ? (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date & Time</TableHead>
                  <TableHead>Student</TableHead>
                  <TableHead>Class</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Photo</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredAttendance.map((record) => {
                  const timestamp = new Date(record.timestamp);
                  return (
                    <TableRow key={record.id}>
                      <TableCell>
                        <div>
                          <p className="font-medium">{format(timestamp, 'MMM dd, yyyy')}</p>
                          <p className="text-sm text-slate-500">{format(timestamp, 'HH:mm')}</p>
                        </div>
                      </TableCell>
                      <TableCell className="font-medium">{record.student_name}</TableCell>
                      <TableCell>{record.class_name}</TableCell>
                      <TableCell>{getStatusBadge(record.status)}</TableCell>
                      <TableCell>
                        {record.photo_url ? (
                          <a 
                            href={`${process.env.REACT_APP_BACKEND_URL}${record.photo_url}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-indigo-600 hover:text-indigo-700"
                          >
                            <ImageIcon className="w-5 h-5" />
                          </a>
                        ) : (
                          <span className="text-slate-300">-</span>
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          ) : (
            <div className="py-12 text-center">
              <CalendarIcon className="w-12 h-12 mx-auto text-slate-300 mb-4" />
              <h3 className="text-lg font-medium text-slate-900 mb-2">No records found</h3>
              <p className="text-slate-500">
                {searchQuery || selectedClass !== 'all' || selectedStatus !== 'all' || dateFrom || dateTo
                  ? 'Try adjusting your filters'
                  : 'Attendance records will appear here'}
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
