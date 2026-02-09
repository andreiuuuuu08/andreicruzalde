import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import axios from 'axios';

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

// Create axios instance
const api = axios.create({
  baseURL: API,
  headers: {
    'Content-Type': 'application/json'
  }
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle auth errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

const AuthContext = createContext(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const storedUser = localStorage.getItem('user');
    const token = localStorage.getItem('token');
    
    if (storedUser && token) {
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);

  const login = async (email, password) => {
    const response = await api.post('/auth/login', { email, password });
    const { access_token, user: userData } = response.data;
    
    localStorage.setItem('token', access_token);
    localStorage.setItem('user', JSON.stringify(userData));
    setUser(userData);
    
    return userData;
  };

  const register = async (userData) => {
    const response = await api.post('/auth/register', userData);
    const { access_token, user: newUser } = response.data;
    
    localStorage.setItem('token', access_token);
    localStorage.setItem('user', JSON.stringify(newUser));
    setUser(newUser);
    
    return newUser;
  };

  const logout = useCallback(() => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
  }, []);

  const updateUser = (updates) => {
    const updatedUser = { ...user, ...updates };
    localStorage.setItem('user', JSON.stringify(updatedUser));
    setUser(updatedUser);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, updateUser }}>
      {children}
    </AuthContext.Provider>
  );
};

// API service functions
export const authAPI = {
  login: (email, password) => api.post('/auth/login', { email, password }),
  register: (data) => api.post('/auth/register', data),
  getMe: () => api.get('/auth/me')
};

export const usersAPI = {
  getAll: (params) => api.get('/users', { params }),
  getById: (id) => api.get(`/users/${id}`),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`)
};

export const classesAPI = {
  getAll: () => api.get('/classes'),
  getById: (id) => api.get(`/classes/${id}`),
  create: (data) => api.post('/classes', data),
  update: (id, data) => api.put(`/classes/${id}`, data),
  delete: (id) => api.delete(`/classes/${id}`),
  getStudents: (id) => api.get(`/classes/${id}/students`),
  enrollStudent: (classId, studentId) => api.post(`/classes/${classId}/enroll`, { student_id: studentId, class_id: classId }),
  unenrollStudent: (classId, studentId) => api.delete(`/classes/${classId}/enroll/${studentId}`)
};

export const faceAPI = {
  register: (userId, faceImages) => api.post('/face/register', { user_id: userId, face_images: faceImages }),
  detect: (image) => api.post('/face/detect', null, { params: { face_image: image } }),
  recognize: (classId, image) => api.post('/face/recognize', null, { params: { class_id: classId, face_image: image } })
};

export const attendanceAPI = {
  mark: (data) => api.post('/attendance/mark', data),
  markManual: (data) => api.post('/attendance/manual', data),
  getAll: (params) => api.get('/attendance', { params }),
  getToday: (classId) => api.get(`/attendance/today/${classId}`),
  getPhoto: (filename) => `${API}/attendance/photo/${filename}`
};

export const analyticsAPI = {
  getOverview: () => api.get('/analytics/overview'),
  getClassAnalytics: (classId) => api.get(`/analytics/class/${classId}`)
};

export const reportsAPI = {
  exportExcel: (params) => api.get('/reports/export/excel', { params, responseType: 'blob' }),
  exportPDF: (params) => api.get('/reports/export/pdf', { params, responseType: 'blob' })
};

export const smsAPI = {
  send: (toPhone, message) => api.post('/sms/send', { to_phone: toPhone, message }),
  getLogs: () => api.get('/sms/logs'),
  getStatus: () => api.get('/sms/status')
};

export const dashboardAPI = {
  getStats: () => api.get('/dashboard/stats')
};

export const galleryAPI = {
  getPhotos: (params) => api.get('/gallery', { params })
};

export const settingsAPI = {
  get: () => api.get('/settings'),
  update: (data) => api.put('/settings', data)
};

export { api };
