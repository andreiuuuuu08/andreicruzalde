import React from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { 
  Camera, 
  Users, 
  BarChart3, 
  Bell, 
  Shield, 
  Clock,
  CheckCircle,
  ArrowRight,
  Smartphone
} from 'lucide-react';

export default function LandingPage() {
  return (
    <div className="min-h-screen bg-slate-50">
      {/* Header */}
      <header className="fixed top-0 left-0 right-0 z-50 glass">
        <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-indigo-700 rounded-lg flex items-center justify-center">
              <Camera className="w-6 h-6 text-white" />
            </div>
            <span className="text-xl font-bold text-slate-900">SmartAttendance</span>
          </div>
          
          <nav className="hidden md:flex items-center gap-8">
            <a href="#features" className="text-slate-600 hover:text-indigo-700 font-medium transition-colors">Features</a>
            <a href="#how-it-works" className="text-slate-600 hover:text-indigo-700 font-medium transition-colors">How it Works</a>
            <a href="#benefits" className="text-slate-600 hover:text-indigo-700 font-medium transition-colors">Benefits</a>
          </nav>

          <div className="flex items-center gap-3">
            <Link to="/login">
              <Button variant="ghost" className="font-medium" data-testid="login-nav-btn">
                Log In
              </Button>
            </Link>
            <Link to="/register">
              <Button className="bg-indigo-700 hover:bg-indigo-800 font-medium" data-testid="get-started-btn">
                Get Started
              </Button>
            </Link>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="relative pt-32 pb-20 overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-teal-50/30" />
        <div className="absolute top-0 right-0 w-1/2 h-full opacity-30">
          <div className="absolute inset-0 bg-gradient-to-l from-indigo-100 to-transparent" />
        </div>
        
        <div className="relative max-w-7xl mx-auto px-6">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div className="animate-fade-in-up">
              <div className="inline-flex items-center gap-2 bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-sm font-medium mb-6">
                <Shield className="w-4 h-4" />
                AI-Powered Verification
              </div>
              
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 leading-tight mb-6">
                Smart Attendance
                <span className="block text-indigo-700">Made Simple</span>
              </h1>
              
              <p className="text-lg text-slate-600 mb-8 max-w-lg leading-relaxed">
                Revolutionary facial recognition system for educational institutions. 
                Verify student attendance instantly, notify parents automatically, 
                and track everything in real-time.
              </p>

              <div className="flex flex-col sm:flex-row gap-4 mb-12">
                <Link to="/register">
                  <Button size="lg" className="bg-indigo-700 hover:bg-indigo-800 text-lg px-8 py-6 btn-hover-lift" data-testid="hero-cta-btn">
                    Start Free Trial
                    <ArrowRight className="ml-2 w-5 h-5" />
                  </Button>
                </Link>
                <Button size="lg" variant="outline" className="text-lg px-8 py-6">
                  Watch Demo
                </Button>
              </div>

              <div className="flex items-center gap-8">
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-teal-600" />
                  <span className="text-slate-600">No hardware required</span>
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-teal-600" />
                  <span className="text-slate-600">Setup in minutes</span>
                </div>
              </div>
            </div>

            <div className="relative animate-fade-in-up stagger-2">
              <div className="relative aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl">
                <img 
                  src="https://images.unsplash.com/photo-1758270704524-596810e891b5?w=800&q=80" 
                  alt="Students in classroom"
                  className="w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent" />
                
                {/* Floating Stats Cards */}
                <div className="absolute bottom-6 left-6 right-6 flex gap-4">
                  <div className="glass rounded-xl p-4 flex-1">
                    <div className="text-2xl font-bold text-white">98.5%</div>
                    <div className="text-sm text-white/80">Accuracy Rate</div>
                  </div>
                  <div className="glass rounded-xl p-4 flex-1">
                    <div className="text-2xl font-bold text-white">&lt;2s</div>
                    <div className="text-sm text-white/80">Recognition Time</div>
                  </div>
                </div>
              </div>

              {/* Floating notification */}
              <div className="absolute -top-4 -right-4 bg-white rounded-xl shadow-lg p-4 animate-slide-in-right stagger-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center">
                    <Bell className="w-5 h-5 text-teal-600" />
                  </div>
                  <div>
                    <div className="text-sm font-medium text-slate-900">SMS Sent</div>
                    <div className="text-xs text-slate-500">Parent notified</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-24 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
              Everything You Need
            </h2>
            <p className="text-lg text-slate-600 max-w-2xl mx-auto">
              Comprehensive attendance management with cutting-edge AI technology
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[
              {
                icon: Camera,
                title: "Facial Recognition",
                description: "Advanced AI-powered face detection and recognition with 98.5% accuracy rate.",
                color: "indigo"
              },
              {
                icon: Bell,
                title: "Instant SMS Alerts",
                description: "Automatic notifications to parents when students arrive or are marked late.",
                color: "teal"
              },
              {
                icon: BarChart3,
                title: "Analytics Dashboard",
                description: "Comprehensive reports and visualizations for attendance patterns.",
                color: "amber"
              },
              {
                icon: Clock,
                title: "Late Tracking",
                description: "Configurable grace periods with automatic late status detection.",
                color: "rose"
              },
              {
                icon: Users,
                title: "Multi-Role Access",
                description: "Separate dashboards for admins, teachers, students, and parents.",
                color: "violet"
              },
              {
                icon: Smartphone,
                title: "Mobile Optimized",
                description: "Works perfectly on tablets and phones for on-the-go attendance.",
                color: "cyan"
              }
            ].map((feature, idx) => (
              <div 
                key={idx}
                className="group p-8 rounded-2xl border border-slate-200 hover:border-indigo-200 card-hover bg-white"
              >
                <div className={`w-14 h-14 rounded-xl bg-${feature.color}-100 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform`}>
                  <feature.icon className={`w-7 h-7 text-${feature.color}-600`} />
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">{feature.title}</h3>
                <p className="text-slate-600 leading-relaxed">{feature.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How it Works */}
      <section id="how-it-works" className="py-24 bg-slate-50">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
              How It Works
            </h2>
            <p className="text-lg text-slate-600 max-w-2xl mx-auto">
              Get started with SmartAttendance in three simple steps
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                step: "01",
                title: "Register Students",
                description: "Add students to the system and capture their face data for recognition."
              },
              {
                step: "02", 
                title: "Start Scanning",
                description: "Use any camera device to scan students as they enter the classroom."
              },
              {
                step: "03",
                title: "Track & Report",
                description: "View real-time analytics and export attendance reports automatically."
              }
            ].map((item, idx) => (
              <div key={idx} className="relative">
                <div className="text-8xl font-bold text-indigo-100 absolute -top-4 -left-2">{item.step}</div>
                <div className="relative pt-12 pl-4">
                  <h3 className="text-xl font-semibold text-slate-900 mb-3">{item.title}</h3>
                  <p className="text-slate-600 leading-relaxed">{item.description}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-24 bg-indigo-700">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
            Ready to Transform Your Attendance System?
          </h2>
          <p className="text-xl text-indigo-100 mb-10 max-w-2xl mx-auto">
            Join hundreds of educational institutions already using SmartAttendance 
            to streamline their attendance management.
          </p>
          <Link to="/register">
            <Button size="lg" className="bg-white text-indigo-700 hover:bg-indigo-50 text-lg px-10 py-6 btn-hover-lift" data-testid="footer-cta-btn">
              Get Started Free
              <ArrowRight className="ml-2 w-5 h-5" />
            </Button>
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="py-12 bg-slate-900">
        <div className="max-w-7xl mx-auto px-6">
          <div className="flex flex-col md:flex-row items-center justify-between gap-6">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                <Camera className="w-6 h-6 text-white" />
              </div>
              <span className="text-xl font-bold text-white">SmartAttendance</span>
            </div>
            
            <p className="text-slate-400 text-sm">
              © 2024 SmartAttendance AI. All rights reserved.
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
}
