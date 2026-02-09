import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Camera, Eye, EyeOff, Loader2 } from 'lucide-react';

export default function RegisterPage() {
  const navigate = useNavigate();
  const { register } = useAuth();
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    role: 'student',
    phone: '',
    parent_phone: '',
    parent_email: ''
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await register(formData);
      toast.success('Account created successfully!');
      navigate('/dashboard');
    } catch (error) {
      toast.error(error.response?.data?.detail || 'Registration failed');
    } finally {
      setLoading(false);
    }
  };

  const isStudent = formData.role === 'student';

  return (
    <div className="min-h-screen flex">
      {/* Left side - Image */}
      <div className="hidden lg:flex lg:w-1/2 relative">
        <img 
          src="https://images.unsplash.com/photo-1758270705518-b61b40527e76?w=1200&q=80"
          alt="Students collaborating"
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
            Create your account and start managing attendance with AI
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
                Fill in your details to get started
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Full Name</Label>
                  <Input
                    id="name"
                    placeholder="John Doe"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                    className="h-11"
                    data-testid="register-name-input"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="name@school.edu"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    required
                    className="h-11"
                    data-testid="register-email-input"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="password">Password</Label>
                  <div className="relative">
                    <Input
                      id="password"
                      type={showPassword ? 'text' : 'password'}
                      placeholder="Create a password"
                      value={formData.password}
                      onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                      required
                      minLength={6}
                      className="h-11 pr-12"
                      data-testid="register-password-input"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                      {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                    </button>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="role">Account Type</Label>
                  <Select 
                    value={formData.role} 
                    onValueChange={(value) => setFormData({ ...formData, role: value })}
                  >
                    <SelectTrigger className="h-11" data-testid="register-role-select">
                      <SelectValue placeholder="Select your role" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="student">Student</SelectItem>
                      <SelectItem value="teacher">Teacher</SelectItem>
                      <SelectItem value="admin">Administrator</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="phone">Phone Number (Optional)</Label>
                  <Input
                    id="phone"
                    type="tel"
                    placeholder="+1234567890"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    className="h-11"
                    data-testid="register-phone-input"
                  />
                </div>

                {isStudent && (
                  <>
                    <div className="pt-2 border-t border-slate-200">
                      <p className="text-sm text-slate-500 mb-3">Parent/Guardian Contact (for notifications)</p>
                    </div>
                    
                    <div className="space-y-2">
                      <Label htmlFor="parent_phone">Parent Phone</Label>
                      <Input
                        id="parent_phone"
                        type="tel"
                        placeholder="+1234567890"
                        value={formData.parent_phone}
                        onChange={(e) => setFormData({ ...formData, parent_phone: e.target.value })}
                        className="h-11"
                        data-testid="register-parent-phone-input"
                      />
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="parent_email">Parent Email</Label>
                      <Input
                        id="parent_email"
                        type="email"
                        placeholder="parent@email.com"
                        value={formData.parent_email}
                        onChange={(e) => setFormData({ ...formData, parent_email: e.target.value })}
                        className="h-11"
                        data-testid="register-parent-email-input"
                      />
                    </div>
                  </>
                )}

                <Button 
                  type="submit" 
                  className="w-full h-11 bg-indigo-700 hover:bg-indigo-800 text-base font-medium mt-2"
                  disabled={loading}
                  data-testid="register-submit-btn"
                >
                  {loading ? (
                    <>
                      <Loader2 className="w-5 h-5 mr-2 animate-spin" />
                      Creating account...
                    </>
                  ) : (
                    'Create Account'
                  )}
                </Button>
              </form>

              <div className="mt-6 text-center">
                <p className="text-slate-600">
                  Already have an account?{' '}
                  <Link to="/login" className="text-indigo-700 hover:text-indigo-800 font-medium">
                    Sign in
                  </Link>
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
