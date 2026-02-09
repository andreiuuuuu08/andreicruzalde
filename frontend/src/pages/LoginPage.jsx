import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { toast } from 'sonner';
import { Camera, Eye, EyeOff, Loader2, AlertCircle, CheckCircle } from 'lucide-react';

export default function LoginPage() {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [touched, setTouched] = useState({
    email: false,
    password: false
  });

  // Validation functions
  const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  };

  const getEmailError = () => {
    if (!touched.email) return '';
    if (!formData.email) return 'Email is required';
    if (!validateEmail(formData.email)) return 'Please enter a valid email address';
    return '';
  };

  const getPasswordError = () => {
    if (!touched.password) return '';
    if (!formData.password) return 'Password is required';
    if (formData.password.length < 6) return 'Password must be at least 6 characters';
    return '';
  };

  const isFormValid = () => {
    return formData.email && 
           formData.password && 
           validateEmail(formData.email) && 
           formData.password.length >= 6;
  };

  const handleBlur = (field) => {
    setTouched(prev => ({ ...prev, [field]: true }));
  };

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    setError(''); // Clear general error when user types
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Mark all fields as touched
    setTouched({ email: true, password: true });
    
    if (!isFormValid()) {
      setError('Please fill in all required fields correctly');
      return;
    }

    setLoading(true);
    setError('');

    try {
      await login(formData.email, formData.password);
      toast.success('Welcome back!');
      navigate('/dashboard');
    } catch (error) {
      const errorMessage = error.response?.data?.detail || 'Invalid email or password. Please try again.';
      setError(errorMessage);
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const emailError = getEmailError();
  const passwordError = getPasswordError();

  return (
    <div className="min-h-screen flex">
      {/* Left side - Image */}
      <div className="hidden lg:flex lg:w-1/2 relative" aria-hidden="true">
        <img 
          src="https://images.unsplash.com/photo-1758611228434-7b5b697abd0a?w=1200&q=80"
          alt=""
          className="absolute inset-0 w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-indigo-900/70" />
        <div className="relative z-10 flex flex-col justify-end p-12">
          <div className="flex items-center gap-3 mb-6">
            <div className="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
              <Camera className="w-7 h-7 text-white" />
            </div>
            <span className="text-2xl font-bold text-white">SmartAttendance</span>
          </div>
          <h2 className="text-3xl font-bold text-white mb-3">
            Welcome back
          </h2>
          <p className="text-indigo-100 text-lg">
            Sign in to access your attendance management dashboard
          </p>
        </div>
      </div>

      {/* Right side - Form */}
      <div className="flex-1 flex items-center justify-center p-8 bg-slate-50">
        <div className="w-full max-w-md">
          {/* Mobile logo */}
          <div className="lg:hidden flex items-center gap-3 mb-8 justify-center">
            <div className="w-10 h-10 bg-indigo-700 rounded-lg flex items-center justify-center">
              <Camera className="w-6 h-6 text-white" />
            </div>
            <span className="text-xl font-bold text-slate-900">SmartAttendance</span>
          </div>

          <Card className="border-0 shadow-xl">
            <CardHeader className="space-y-1 pb-6">
              <CardTitle className="text-2xl font-bold">Sign In</CardTitle>
              <CardDescription>
                Enter your credentials to access your account
              </CardDescription>
            </CardHeader>
            <CardContent>
              {/* Error Alert */}
              {error && (
                <Alert variant="destructive" className="mb-6" role="alert">
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}

              <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                {/* Email Field */}
                <div className="space-y-2">
                  <Label htmlFor="email" className="flex items-center gap-1">
                    Email Address
                    <span className="text-red-500" aria-hidden="true">*</span>
                  </Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="name@school.edu"
                    value={formData.email}
                    onChange={(e) => handleChange('email', e.target.value)}
                    onBlur={() => handleBlur('email')}
                    required
                    aria-required="true"
                    aria-invalid={!!emailError}
                    aria-describedby={emailError ? "email-error" : undefined}
                    className={`h-12 ${emailError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                    data-testid="login-email-input"
                    autoComplete="email"
                    autoFocus
                  />
                  {emailError && (
                    <p id="email-error" className="text-sm text-red-500 flex items-center gap-1" role="alert">
                      <AlertCircle className="w-4 h-4" />
                      {emailError}
                    </p>
                  )}
                </div>

                {/* Password Field */}
                <div className="space-y-2">
                  <Label htmlFor="password" className="flex items-center gap-1">
                    Password
                    <span className="text-red-500" aria-hidden="true">*</span>
                  </Label>
                  <div className="relative">
                    <Input
                      id="password"
                      type={showPassword ? 'text' : 'password'}
                      placeholder="Enter your password"
                      value={formData.password}
                      onChange={(e) => handleChange('password', e.target.value)}
                      onBlur={() => handleBlur('password')}
                      required
                      aria-required="true"
                      aria-invalid={!!passwordError}
                      aria-describedby={passwordError ? "password-error" : undefined}
                      className={`h-12 pr-12 ${passwordError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                      data-testid="login-password-input"
                      autoComplete="current-password"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded"
                      aria-label={showPassword ? 'Hide password' : 'Show password'}
                    >
                      {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                    </button>
                  </div>
                  {passwordError && (
                    <p id="password-error" className="text-sm text-red-500 flex items-center gap-1" role="alert">
                      <AlertCircle className="w-4 h-4" />
                      {passwordError}
                    </p>
                  )}
                </div>

                {/* Submit Button */}
                <Button 
                  type="submit" 
                  className="w-full h-12 bg-indigo-700 hover:bg-indigo-800 text-base font-medium"
                  disabled={loading}
                  data-testid="login-submit-btn"
                  aria-busy={loading}
                >
                  {loading ? (
                    <>
                      <Loader2 className="w-5 h-5 mr-2 animate-spin" aria-hidden="true" />
                      <span>Signing in...</span>
                    </>
                  ) : (
                    'Sign In'
                  )}
                </Button>
              </form>

              <div className="mt-6 text-center">
                <p className="text-slate-600">
                  Don't have an account?{' '}
                  <Link 
                    to="/register" 
                    className="text-indigo-700 hover:text-indigo-800 font-medium focus:outline-none focus:underline"
                  >
                    Create one
                  </Link>
                </p>
              </div>
            </CardContent>
          </Card>

          <p className="text-center text-sm text-slate-500 mt-6">
            By signing in, you agree to our Terms of Service and Privacy Policy
          </p>

          {/* Screen reader only instructions */}
          <div className="sr-only" aria-live="polite">
            {loading && 'Signing in, please wait...'}
          </div>
        </div>
      </div>
    </div>
  );
}
