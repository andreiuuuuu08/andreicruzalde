import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { toast } from 'sonner';
import { Camera, Eye, EyeOff, Loader2, AlertCircle, CheckCircle, ArrowLeft, KeyRound } from 'lucide-react';

const API_URL = process.env.REACT_APP_BACKEND_URL;

export default function ResetPasswordPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');

  const [loading, setLoading] = useState(false);
  const [verifying, setVerifying] = useState(true);
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [tokenValid, setTokenValid] = useState(false);
  const [userEmail, setUserEmail] = useState('');
  const [formData, setFormData] = useState({
    password: '',
    confirmPassword: ''
  });
  const [touched, setTouched] = useState({
    password: false,
    confirmPassword: false
  });

  useEffect(() => {
    if (token) {
      verifyToken();
    } else {
      setVerifying(false);
      setError('No reset token provided. Please request a new password reset.');
    }
  }, [token]);

  const verifyToken = async () => {
    try {
      const response = await fetch(`${API_URL}/api/auth/verify-reset-token?token=${token}`, {
        method: 'POST'
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.detail || 'Invalid or expired token');
      }

      setTokenValid(true);
      setUserEmail(data.email);
    } catch (error) {
      setError(error.message || 'Invalid or expired reset link. Please request a new one.');
      setTokenValid(false);
    } finally {
      setVerifying(false);
    }
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

  const isFormValid = () => {
    return formData.password &&
           formData.password.length >= 6 &&
           formData.password === formData.confirmPassword;
  };

  const handleBlur = (field) => {
    setTouched(prev => ({ ...prev, [field]: true }));
  };

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    setError('');
  };

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

  const handleSubmit = async (e) => {
    e.preventDefault();
    setTouched({ password: true, confirmPassword: true });

    if (!isFormValid()) {
      setError('Please fill in all fields correctly');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await fetch(`${API_URL}/api/auth/reset-password`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          token,
          new_password: formData.password
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.detail || 'Failed to reset password');
      }

      setSuccess(true);
      toast.success('Password reset successfully!');
    } catch (error) {
      setError(error.message || 'Something went wrong. Please try again.');
      toast.error(error.message);
    } finally {
      setLoading(false);
    }
  };

  const passwordError = getPasswordError();
  const confirmPasswordError = getConfirmPasswordError();
  const passwordStrength = getPasswordStrength();

  // Loading state while verifying token
  if (verifying) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="text-center">
          <Loader2 className="w-12 h-12 border-4 border-indigo-600 mx-auto animate-spin mb-4" />
          <p className="text-slate-600">Verifying reset link...</p>
        </div>
      </div>
    );
  }

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
            Create new password
          </h2>
          <p className="text-indigo-100 text-lg">
            Choose a strong password to secure your account
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
              <CardTitle className="text-2xl font-bold">
                {success ? 'Password Reset!' : 'Set New Password'}
              </CardTitle>
              <CardDescription>
                {success 
                  ? "Your password has been changed successfully"
                  : tokenValid 
                    ? `Create a new password for ${userEmail}`
                    : "There was a problem with your reset link"
                }
              </CardDescription>
            </CardHeader>
            <CardContent>
              {/* Invalid Token State */}
              {!tokenValid && !success && (
                <div className="space-y-6">
                  <Alert variant="destructive" role="alert">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                  </Alert>

                  <div className="text-center space-y-4">
                    <p className="text-slate-600 text-sm">
                      The password reset link is invalid or has expired. 
                      Please request a new one.
                    </p>
                    <Link to="/forgot-password">
                      <Button className="w-full bg-indigo-700 hover:bg-indigo-800">
                        Request New Reset Link
                      </Button>
                    </Link>
                  </div>
                </div>
              )}

              {/* Success State */}
              {success && (
                <div className="space-y-6">
                  <div className="text-center">
                    <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <CheckCircle className="w-8 h-8 text-green-600" />
                    </div>
                    <h3 className="text-lg font-medium text-slate-900 mb-2">All done!</h3>
                    <p className="text-slate-600 text-sm">
                      Your password has been reset. You can now sign in with your new password.
                    </p>
                  </div>

                  <Link to="/login">
                    <Button className="w-full bg-indigo-700 hover:bg-indigo-800">
                      Sign In
                    </Button>
                  </Link>
                </div>
              )}

              {/* Reset Form */}
              {tokenValid && !success && (
                <>
                  {error && (
                    <Alert variant="destructive" className="mb-6" role="alert">
                      <AlertCircle className="h-4 w-4" />
                      <AlertDescription>{error}</AlertDescription>
                    </Alert>
                  )}

                  <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                    {/* New Password Field */}
                    <div className="space-y-2">
                      <Label htmlFor="password" className="flex items-center gap-1">
                        New Password
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
                          className={`h-12 pr-12 ${passwordError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                          data-testid="reset-password-input"
                          autoComplete="new-password"
                          autoFocus
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
                        placeholder="Confirm your new password"
                        value={formData.confirmPassword}
                        onChange={(e) => handleChange('confirmPassword', e.target.value)}
                        onBlur={() => handleBlur('confirmPassword')}
                        required
                        aria-required="true"
                        aria-invalid={!!confirmPasswordError}
                        aria-describedby={confirmPasswordError ? "confirm-password-error" : undefined}
                        className={`h-12 ${confirmPasswordError ? 'border-red-500 focus-visible:ring-red-500' : formData.confirmPassword && formData.password === formData.confirmPassword ? 'border-green-500' : ''}`}
                        data-testid="reset-confirm-password-input"
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

                    {/* Submit Button */}
                    <Button 
                      type="submit" 
                      className="w-full h-12 bg-indigo-700 hover:bg-indigo-800 text-base font-medium"
                      disabled={loading}
                      data-testid="reset-submit-btn"
                      aria-busy={loading}
                    >
                      {loading ? (
                        <>
                          <Loader2 className="w-5 h-5 mr-2 animate-spin" aria-hidden="true" />
                          <span>Resetting password...</span>
                        </>
                      ) : (
                        <>
                          <KeyRound className="w-5 h-5 mr-2" />
                          Reset Password
                        </>
                      )}
                    </Button>
                  </form>
                </>
              )}

              <div className="mt-6 text-center">
                <Link 
                  to="/login" 
                  className="inline-flex items-center text-indigo-700 hover:text-indigo-800 font-medium focus:outline-none focus:underline"
                >
                  <ArrowLeft className="w-4 h-4 mr-2" />
                  Back to Sign In
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
