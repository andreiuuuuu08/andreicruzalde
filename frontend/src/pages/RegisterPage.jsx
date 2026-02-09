import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Camera, Eye, EyeOff, Loader2, AlertCircle, CheckCircle, Info } from 'lucide-react';

export default function RegisterPage() {
  const navigate = useNavigate();
  const { register } = useAuth();
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    role: 'student',
    phone: '',
    parent_phone: '',
    parent_email: ''
  });
  const [touched, setTouched] = useState({
    name: false,
    email: false,
    password: false,
    confirmPassword: false,
    phone: false,
    parent_phone: false,
    parent_email: false
  });

  // Validation functions
  const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  };

  const validatePhone = (phone) => {
    if (!phone) return true; // Optional field
    const phoneRegex = /^\+?[1-9]\d{6,14}$/;
    return phoneRegex.test(phone.replace(/[\s-]/g, ''));
  };

  const getNameError = () => {
    if (!touched.name) return '';
    if (!formData.name) return 'Full name is required';
    if (formData.name.length < 2) return 'Name must be at least 2 characters';
    return '';
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

  const getConfirmPasswordError = () => {
    if (!touched.confirmPassword) return '';
    if (!formData.confirmPassword) return 'Please confirm your password';
    if (formData.password !== formData.confirmPassword) return 'Passwords do not match';
    return '';
  };

  const getPhoneError = () => {
    if (!touched.phone || !formData.phone) return '';
    if (!validatePhone(formData.phone)) return 'Please enter a valid phone number';
    return '';
  };

  const getParentPhoneError = () => {
    if (!touched.parent_phone || !formData.parent_phone) return '';
    if (!validatePhone(formData.parent_phone)) return 'Please enter a valid phone number';
    return '';
  };

  const getParentEmailError = () => {
    if (!touched.parent_email || !formData.parent_email) return '';
    if (!validateEmail(formData.parent_email)) return 'Please enter a valid email address';
    return '';
  };

  const isFormValid = () => {
    return formData.name && 
           formData.name.length >= 2 &&
           formData.email && 
           validateEmail(formData.email) && 
           formData.password &&
           formData.password.length >= 6 &&
           formData.password === formData.confirmPassword &&
           (!formData.phone || validatePhone(formData.phone)) &&
           (!formData.parent_phone || validatePhone(formData.parent_phone)) &&
           (!formData.parent_email || validateEmail(formData.parent_email));
  };

  const handleBlur = (field) => {
    setTouched(prev => ({ ...prev, [field]: true }));
  };

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Mark required fields as touched
    setTouched({
      name: true,
      email: true,
      password: true,
      confirmPassword: true,
      phone: !!formData.phone,
      parent_phone: !!formData.parent_phone,
      parent_email: !!formData.parent_email
    });
    
    if (!isFormValid()) {
      setError('Please fill in all required fields correctly');
      return;
    }

    setLoading(true);
    setError('');

    try {
      // Remove confirmPassword before sending
      const { confirmPassword, ...registrationData } = formData;
      await register(registrationData);
      toast.success('Account created successfully! Welcome to SmartAttendance.');
      navigate('/dashboard');
    } catch (error) {
      const errorMessage = error.response?.data?.detail || 'Registration failed. Please try again.';
      setError(errorMessage);
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const nameError = getNameError();
  const emailError = getEmailError();
  const passwordError = getPasswordError();
  const confirmPasswordError = getConfirmPasswordError();
  const phoneError = getPhoneError();
  const parentPhoneError = getParentPhoneError();
  const parentEmailError = getParentEmailError();
  const isStudent = formData.role === 'student';

  // Password strength indicator
  const getPasswordStrength = () => {
    const password = formData.password;
    if (!password) return { strength: 0, label: '', color: '' };
    
    let strength = 0;
    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    if (strength <= 2) return { strength, label: 'Weak', color: 'bg-red-500' };
    if (strength <= 3) return { strength, label: 'Medium', color: 'bg-amber-500' };
    return { strength, label: 'Strong', color: 'bg-green-500' };
  };

  const passwordStrength = getPasswordStrength();

  return (
    <div className="min-h-screen flex">
      {/* Left side - Image */}
      <div className="hidden lg:flex lg:w-1/2 relative" aria-hidden="true">
        <img 
          src="https://images.unsplash.com/photo-1758270705518-b61b40527e76?w=1200&q=80"
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
            Join SmartAttendance
          </h2>
          <p className="text-indigo-100 text-lg">
            Create your account and start managing attendance with AI-powered face recognition
          </p>
        </div>
      </div>

      {/* Right side - Form */}
      <div className="flex-1 flex items-center justify-center p-8 bg-slate-50 overflow-y-auto">
        <div className="w-full max-w-md py-8">
          {/* Mobile logo */}
          <div className="lg:hidden flex items-center gap-3 mb-8 justify-center">
            <div className="w-10 h-10 bg-indigo-700 rounded-lg flex items-center justify-center">
              <Camera className="w-6 h-6 text-white" />
            </div>
            <span className="text-xl font-bold text-slate-900">SmartAttendance</span>
          </div>

          <Card className="border-0 shadow-xl">
            <CardHeader className="space-y-1 pb-4">
              <CardTitle className="text-2xl font-bold">Create Account</CardTitle>
              <CardDescription>
                Fill in your details to get started. Fields marked with <span className="text-red-500">*</span> are required.
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

              <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                {/* Full Name Field */}
                <div className="space-y-2">
                  <Label htmlFor="name" className="flex items-center gap-1">
                    Full Name
                    <span className="text-red-500" aria-hidden="true">*</span>
                  </Label>
                  <Input
                    id="name"
                    type="text"
                    placeholder="John Doe"
                    value={formData.name}
                    onChange={(e) => handleChange('name', e.target.value)}
                    onBlur={() => handleBlur('name')}
                    required
                    aria-required="true"
                    aria-invalid={!!nameError}
                    aria-describedby={nameError ? "name-error" : undefined}
                    className={`h-11 ${nameError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                    data-testid="register-name-input"
                    autoComplete="name"
                    autoFocus
                  />
                  {nameError && (
                    <p id="name-error" className="text-sm text-red-500 flex items-center gap-1" role="alert">
                      <AlertCircle className="w-4 h-4" />
                      {nameError}
                    </p>
                  )}
                </div>

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
                    className={`h-11 ${emailError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                    data-testid="register-email-input"
                    autoComplete="email"
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
                      placeholder="Create a strong password"
                      value={formData.password}
                      onChange={(e) => handleChange('password', e.target.value)}
                      onBlur={() => handleBlur('password')}
                      required
                      minLength={6}
                      aria-required="true"
                      aria-invalid={!!passwordError}
                      aria-describedby="password-requirements password-error"
                      className={`h-11 pr-12 ${passwordError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                      data-testid="register-password-input"
                      autoComplete="new-password"
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
                  {/* Password strength indicator */}
                  {formData.password && (
                    <div className="space-y-1">
                      <div className="flex gap-1">
                        {[1, 2, 3, 4, 5].map((i) => (
                          <div 
                            key={i} 
                            className={`h-1 flex-1 rounded ${i <= passwordStrength.strength ? passwordStrength.color : 'bg-slate-200'}`}
                          />
                        ))}
                      </div>
                      <p className="text-xs text-slate-500">
                        Password strength: <span className={passwordStrength.strength <= 2 ? 'text-red-500' : passwordStrength.strength <= 3 ? 'text-amber-500' : 'text-green-500'}>{passwordStrength.label}</span>
                      </p>
                    </div>
                  )}
                  <p id="password-requirements" className="text-xs text-slate-500">
                    Minimum 6 characters. Use uppercase, numbers, and symbols for stronger security.
                  </p>
                  {passwordError && (
                    <p id="password-error" className="text-sm text-red-500 flex items-center gap-1" role="alert">
                      <AlertCircle className="w-4 h-4" />
                      {passwordError}
                    </p>
                  )}
                </div>

                {/* Confirm Password Field */}
                <div className="space-y-2">
                  <Label htmlFor="confirmPassword" className="flex items-center gap-1">
                    Confirm Password
                    <span className="text-red-500" aria-hidden="true">*</span>
                  </Label>
                  <Input
                    id="confirmPassword"
                    type={showPassword ? 'text' : 'password'}
                    placeholder="Confirm your password"
                    value={formData.confirmPassword}
                    onChange={(e) => handleChange('confirmPassword', e.target.value)}
                    onBlur={() => handleBlur('confirmPassword')}
                    required
                    aria-required="true"
                    aria-invalid={!!confirmPasswordError}
                    aria-describedby={confirmPasswordError ? "confirm-password-error" : undefined}
                    className={`h-11 ${confirmPasswordError ? 'border-red-500 focus-visible:ring-red-500' : formData.confirmPassword && formData.password === formData.confirmPassword ? 'border-green-500' : ''}`}
                    data-testid="register-confirm-password-input"
                    autoComplete="new-password"
                  />
                  {formData.confirmPassword && formData.password === formData.confirmPassword && (
                    <p className="text-sm text-green-600 flex items-center gap-1">
                      <CheckCircle className="w-4 h-4" />
                      Passwords match
                    </p>
                  )}
                  {confirmPasswordError && (
                    <p id="confirm-password-error" className="text-sm text-red-500 flex items-center gap-1" role="alert">
                      <AlertCircle className="w-4 h-4" />
                      {confirmPasswordError}
                    </p>
                  )}
                </div>

                {/* Account Type Field */}
                <div className="space-y-2">
                  <Label htmlFor="role" className="flex items-center gap-1">
                    Account Type
                    <span className="text-red-500" aria-hidden="true">*</span>
                  </Label>
                  <Select 
                    value={formData.role} 
                    onValueChange={(value) => handleChange('role', value)}
                  >
                    <SelectTrigger 
                      className="h-11" 
                      data-testid="register-role-select"
                      aria-label="Select your account type"
                    >
                      <SelectValue placeholder="Select your role" />
                    </SelectTrigger>
                    <SelectContent position="popper" sideOffset={4}>
                      <SelectItem value="student" data-testid="role-student">
                        <div className="flex flex-col">
                          <span>Student</span>
                          <span className="text-xs text-slate-500">View attendance, register face</span>
                        </div>
                      </SelectItem>
                      <SelectItem value="teacher" data-testid="role-teacher">
                        <div className="flex flex-col">
                          <span>Teacher</span>
                          <span className="text-xs text-slate-500">Manage classes, take attendance</span>
                        </div>
                      </SelectItem>
                      <SelectItem value="admin" data-testid="role-admin">
                        <div className="flex flex-col">
                          <span>Administrator</span>
                          <span className="text-xs text-slate-500">Full system access</span>
                        </div>
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                {/* Phone Field */}
                <div className="space-y-2">
                  <Label htmlFor="phone">Phone Number (Optional)</Label>
                  <Input
                    id="phone"
                    type="tel"
                    placeholder="+1234567890"
                    value={formData.phone}
                    onChange={(e) => handleChange('phone', e.target.value)}
                    onBlur={() => handleBlur('phone')}
                    aria-invalid={!!phoneError}
                    aria-describedby={phoneError ? "phone-error" : "phone-hint"}
                    className={`h-11 ${phoneError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                    data-testid="register-phone-input"
                    autoComplete="tel"
                  />
                  <p id="phone-hint" className="text-xs text-slate-500">
                    Include country code (e.g., +1 for US)
                  </p>
                  {phoneError && (
                    <p id="phone-error" className="text-sm text-red-500 flex items-center gap-1" role="alert">
                      <AlertCircle className="w-4 h-4" />
                      {phoneError}
                    </p>
                  )}
                </div>

                {/* Parent/Guardian Section - Only for Students */}
                {isStudent && (
                  <fieldset className="pt-4 border-t border-slate-200">
                    <legend className="text-sm font-medium text-slate-700 mb-3 flex items-center gap-2">
                      <Info className="w-4 h-4 text-indigo-600" />
                      Parent/Guardian Contact (for SMS notifications)
                    </legend>
                    
                    <div className="space-y-4">
                      <div className="space-y-2">
                        <Label htmlFor="parent_phone">Parent Phone Number</Label>
                        <Input
                          id="parent_phone"
                          type="tel"
                          placeholder="+1234567890"
                          value={formData.parent_phone}
                          onChange={(e) => handleChange('parent_phone', e.target.value)}
                          onBlur={() => handleBlur('parent_phone')}
                          aria-invalid={!!parentPhoneError}
                          aria-describedby={parentPhoneError ? "parent-phone-error" : "parent-phone-hint"}
                          className={`h-11 ${parentPhoneError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                          data-testid="register-parent-phone-input"
                          autoComplete="tel"
                        />
                        <p id="parent-phone-hint" className="text-xs text-slate-500">
                          SMS notifications will be sent to this number
                        </p>
                        {parentPhoneError && (
                          <p id="parent-phone-error" className="text-sm text-red-500 flex items-center gap-1" role="alert">
                            <AlertCircle className="w-4 h-4" />
                            {parentPhoneError}
                          </p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <Label htmlFor="parent_email">Parent Email</Label>
                        <Input
                          id="parent_email"
                          type="email"
                          placeholder="parent@email.com"
                          value={formData.parent_email}
                          onChange={(e) => handleChange('parent_email', e.target.value)}
                          onBlur={() => handleBlur('parent_email')}
                          aria-invalid={!!parentEmailError}
                          aria-describedby={parentEmailError ? "parent-email-error" : undefined}
                          className={`h-11 ${parentEmailError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                          data-testid="register-parent-email-input"
                          autoComplete="email"
                        />
                        {parentEmailError && (
                          <p id="parent-email-error" className="text-sm text-red-500 flex items-center gap-1" role="alert">
                            <AlertCircle className="w-4 h-4" />
                            {parentEmailError}
                          </p>
                        )}
                      </div>
                    </div>
                  </fieldset>
                )}

                {/* Submit Button */}
                <Button 
                  type="submit" 
                  className="w-full h-11 bg-indigo-700 hover:bg-indigo-800 text-base font-medium mt-2"
                  disabled={loading}
                  data-testid="register-submit-btn"
                  aria-busy={loading}
                >
                  {loading ? (
                    <>
                      <Loader2 className="w-5 h-5 mr-2 animate-spin" aria-hidden="true" />
                      <span>Creating account...</span>
                    </>
                  ) : (
                    'Create Account'
                  )}
                </Button>
              </form>

              <div className="mt-6 text-center">
                <p className="text-slate-600">
                  Already have an account?{' '}
                  <Link 
                    to="/login" 
                    className="text-indigo-700 hover:text-indigo-800 font-medium focus:outline-none focus:underline"
                  >
                    Sign in
                  </Link>
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Screen reader only instructions */}
          <div className="sr-only" aria-live="polite">
            {loading && 'Creating your account, please wait...'}
          </div>
        </div>
      </div>
    </div>
  );
}
