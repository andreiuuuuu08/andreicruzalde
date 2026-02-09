import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { toast } from 'sonner';
import { Camera, Loader2, AlertCircle, CheckCircle, ArrowLeft, Mail } from 'lucide-react';

const API_URL = process.env.REACT_APP_BACKEND_URL;

export default function ForgotPasswordPage() {
  const [loading, setLoading] = useState(false);
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [resetToken, setResetToken] = useState('');
  const [touched, setTouched] = useState(false);

  const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  };

  const getEmailError = () => {
    if (!touched) return '';
    if (!email) return 'Email is required';
    if (!validateEmail(email)) return 'Please enter a valid email address';
    return '';
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setTouched(true);

    if (!email || !validateEmail(email)) {
      setError('Please enter a valid email address');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await fetch(`${API_URL}/api/auth/forgot-password`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.detail || 'Failed to send reset link');
      }

      setSuccess(true);
      setResetToken(data.reset_token || '');
      toast.success('Password reset instructions sent!');
    } catch (error) {
      setError(error.message || 'Something went wrong. Please try again.');
      toast.error(error.message);
    } finally {
      setLoading(false);
    }
  };

  const emailError = getEmailError();

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
            Forgot your password?
          </h2>
          <p className="text-indigo-100 text-lg">
            No worries! Enter your email and we'll help you reset it.
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
              <CardTitle className="text-2xl font-bold">Reset Password</CardTitle>
              <CardDescription>
                {success 
                  ? "Check your email for reset instructions"
                  : "Enter your email address and we'll send you a link to reset your password"
                }
              </CardDescription>
            </CardHeader>
            <CardContent>
              {!success ? (
                <>
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
                        value={email}
                        onChange={(e) => { setEmail(e.target.value); setError(''); }}
                        onBlur={() => setTouched(true)}
                        required
                        aria-required="true"
                        aria-invalid={!!emailError}
                        aria-describedby={emailError ? "email-error" : undefined}
                        className={`h-12 ${emailError ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                        data-testid="forgot-email-input"
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

                    {/* Submit Button */}
                    <Button 
                      type="submit" 
                      className="w-full h-12 bg-indigo-700 hover:bg-indigo-800 text-base font-medium"
                      disabled={loading}
                      data-testid="forgot-submit-btn"
                      aria-busy={loading}
                    >
                      {loading ? (
                        <>
                          <Loader2 className="w-5 h-5 mr-2 animate-spin" aria-hidden="true" />
                          <span>Sending...</span>
                        </>
                      ) : (
                        <>
                          <Mail className="w-5 h-5 mr-2" />
                          Send Reset Link
                        </>
                      )}
                    </Button>
                  </form>
                </>
              ) : (
                <div className="space-y-6">
                  {/* Success Message */}
                  <div className="text-center">
                    <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <CheckCircle className="w-8 h-8 text-green-600" />
                    </div>
                    <h3 className="text-lg font-medium text-slate-900 mb-2">Check your email</h3>
                    <p className="text-slate-600 text-sm">
                      We've sent password reset instructions to <strong>{email}</strong>
                    </p>
                  </div>

                  {/* Reset Token Display (for demo purposes) */}
                  {resetToken && (
                    <Alert className="bg-amber-50 border-amber-200">
                      <AlertCircle className="h-4 w-4 text-amber-600" />
                      <AlertDescription className="text-amber-800">
                        <strong>Demo Mode:</strong> Use this link to reset your password:
                        <Link 
                          to={`/reset-password?token=${resetToken}`}
                          className="block mt-2 text-indigo-600 hover:text-indigo-700 underline break-all"
                        >
                          Click here to reset password
                        </Link>
                      </AlertDescription>
                    </Alert>
                  )}

                  <div className="space-y-3">
                    <Button 
                      variant="outline"
                      className="w-full"
                      onClick={() => { setSuccess(false); setEmail(''); setResetToken(''); }}
                    >
                      Try another email
                    </Button>
                  </div>
                </div>
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
