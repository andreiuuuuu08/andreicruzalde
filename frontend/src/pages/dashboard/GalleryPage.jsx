import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { galleryAPI, classesAPI } from '@/context/AuthContext';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
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
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { format } from 'date-fns';
import { toast } from 'sonner';
import { 
  Image, 
  CalendarIcon,
  CheckCircle,
  Clock,
  X,
  Filter
} from 'lucide-react';
import { cn } from '@/lib/utils';

export default function GalleryPage() {
  const { user } = useAuth();
  const [photos, setPhotos] = useState([]);
  const [classes, setClasses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedClass, setSelectedClass] = useState('all');
  const [selectedDate, setSelectedDate] = useState(null);
  const [selectedPhoto, setSelectedPhoto] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    loadPhotos();
  }, [selectedClass, selectedDate]);

  const loadData = async () => {
    try {
      const response = await classesAPI.getAll();
      setClasses(response.data);
    } catch (error) {
      console.error('Failed to load classes:', error);
    }
  };

  const loadPhotos = async () => {
    setLoading(true);
    try {
      const params = { limit: 50 };
      if (selectedClass !== 'all') params.class_id = selectedClass;
      if (selectedDate) params.date = format(selectedDate, 'yyyy-MM-dd');

      const response = await galleryAPI.getPhotos(params);
      setPhotos(response.data);
    } catch (error) {
      toast.error('Failed to load photos');
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'present': return 'bg-teal-100 text-teal-700';
      case 'late': return 'bg-amber-100 text-amber-700';
      default: return 'bg-slate-100 text-slate-700';
    }
  };

  return (
    <div className="space-y-6" data-testid="gallery-page">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Attendance Gallery</h1>
        <p className="text-slate-600">View captured attendance photos with timestamps</p>
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
          <div className="flex flex-wrap gap-4">
            <Select value={selectedClass} onValueChange={setSelectedClass}>
              <SelectTrigger className="w-48">
                <SelectValue placeholder="All Classes" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Classes</SelectItem>
                {classes.map(cls => (
                  <SelectItem key={cls.id} value={cls.id}>{cls.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>

            <Popover>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  className={cn(
                    "w-48 justify-start text-left font-normal",
                    !selectedDate && "text-muted-foreground"
                  )}
                >
                  <CalendarIcon className="mr-2 h-4 w-4" />
                  {selectedDate ? format(selectedDate, "PPP") : "Select date"}
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                  mode="single"
                  selected={selectedDate}
                  onSelect={setSelectedDate}
                  initialFocus
                />
              </PopoverContent>
            </Popover>

            {(selectedClass !== 'all' || selectedDate) && (
              <Button 
                variant="ghost" 
                onClick={() => { 
                  setSelectedClass('all'); 
                  setSelectedDate(null); 
                }}
              >
                Clear Filters
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Photo Grid */}
      {loading ? (
        <div className="flex items-center justify-center h-64">
          <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin" />
        </div>
      ) : photos.length > 0 ? (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
          {photos.map((photo) => {
            const timestamp = new Date(photo.timestamp);
            return (
              <Card 
                key={photo.id} 
                className="overflow-hidden cursor-pointer card-hover"
                onClick={() => setSelectedPhoto(photo)}
              >
                <div className="aspect-square relative bg-slate-100">
                  <img
                    src={`${process.env.REACT_APP_BACKEND_URL}${photo.photo_url}`}
                    alt={photo.student_name}
                    className="w-full h-full object-cover"
                    onError={(e) => {
                      e.target.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="%23f1f5f9" width="100" height="100"/><text x="50" y="50" text-anchor="middle" dy=".3em" fill="%2394a3b8" font-size="40">?</text></svg>';
                    }}
                  />
                  <div className="absolute top-2 right-2">
                    <Badge className={getStatusColor(photo.status)}>
                      {photo.status === 'present' ? (
                        <CheckCircle className="w-3 h-3 mr-1" />
                      ) : (
                        <Clock className="w-3 h-3 mr-1" />
                      )}
                      {photo.status}
                    </Badge>
                  </div>
                </div>
                <CardContent className="p-3">
                  <p className="font-medium text-slate-900 truncate">{photo.student_name}</p>
                  <p className="text-xs text-slate-500">{photo.class_name}</p>
                  <p className="text-xs text-slate-400 mt-1">
                    {format(timestamp, 'MMM dd, yyyy')} • {format(timestamp, 'HH:mm')}
                  </p>
                </CardContent>
              </Card>
            );
          })}
        </div>
      ) : (
        <Card>
          <CardContent className="py-12 text-center">
            <Image className="w-12 h-12 mx-auto text-slate-300 mb-4" />
            <h3 className="text-lg font-medium text-slate-900 mb-2">No photos found</h3>
            <p className="text-slate-500">
              {selectedClass !== 'all' || selectedDate 
                ? 'Try adjusting your filters'
                : 'Attendance photos will appear here after scanning'}
            </p>
          </CardContent>
        </Card>
      )}

      {/* Photo Detail Dialog */}
      <Dialog open={!!selectedPhoto} onOpenChange={() => setSelectedPhoto(null)}>
        <DialogContent className="max-w-2xl">
          {selectedPhoto && (
            <>
              <DialogHeader>
                <DialogTitle>{selectedPhoto.student_name}</DialogTitle>
              </DialogHeader>
              <div className="space-y-4">
                <div className="aspect-video relative rounded-lg overflow-hidden bg-slate-100">
                  <img
                    src={`${process.env.REACT_APP_BACKEND_URL}${selectedPhoto.photo_url}`}
                    alt={selectedPhoto.student_name}
                    className="w-full h-full object-contain"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <p className="text-sm text-slate-500">Class</p>
                    <p className="font-medium">{selectedPhoto.class_name}</p>
                  </div>
                  <div>
                    <p className="text-sm text-slate-500">Status</p>
                    <Badge className={getStatusColor(selectedPhoto.status)}>
                      {selectedPhoto.status}
                    </Badge>
                  </div>
                  <div>
                    <p className="text-sm text-slate-500">Date</p>
                    <p className="font-medium">
                      {format(new Date(selectedPhoto.timestamp), 'MMMM dd, yyyy')}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-slate-500">Time</p>
                    <p className="font-medium">
                      {format(new Date(selectedPhoto.timestamp), 'HH:mm:ss')}
                    </p>
                  </div>
                </div>
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
