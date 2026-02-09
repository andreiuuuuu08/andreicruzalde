import React, { useState, useEffect } from 'react';
import { Outlet, Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { useTheme } from '@/context/ThemeContext';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { 
  Home, 
  BookOpen, 
  Users, 
  Calendar, 
  Camera, 
  BarChart3, 
  Settings,
  LogOut,
  Menu,
  X,
  ChevronRight,
  ChevronLeft,
  User,
  Sun,
  Moon,
  PanelLeftClose,
  PanelLeft
} from 'lucide-react';
import { cn } from '@/lib/utils';

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: Home, roles: ['admin', 'teacher', 'student'] },
  { name: 'Classes', href: '/dashboard/classes', icon: BookOpen, roles: ['admin', 'teacher', 'student'] },
  { name: 'Students', href: '/dashboard/students', icon: Users, roles: ['admin', 'teacher'] },
  { name: 'Attendance', href: '/dashboard/attendance', icon: Calendar, roles: ['admin', 'teacher', 'student'] },
  { name: 'Scanner', href: '/dashboard/scanner', icon: Camera, roles: ['admin', 'teacher'] },
  { name: 'Reports', href: '/dashboard/reports', icon: BarChart3, roles: ['admin', 'teacher'] },
  { name: 'Settings', href: '/dashboard/settings', icon: Settings, roles: ['admin', 'teacher', 'student'] },
];

export default function DashboardLayout() {
  const { user, logout } = useAuth();
  const { theme, toggleTheme, isDark } = useTheme();
  const location = useLocation();
  const navigate = useNavigate();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [sidebarCollapsed, setSidebarCollapsed] = useState(() => {
    const saved = localStorage.getItem('sidebarCollapsed');
    return saved === 'true';
  });

  // Save sidebar state to localStorage
  useEffect(() => {
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed.toString());
  }, [sidebarCollapsed]);

  const filteredNavigation = navigation.filter(item => item.roles.includes(user?.role));

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase() || 'U';
  };

  const toggleSidebarCollapse = () => {
    setSidebarCollapsed(prev => !prev);
  };

  return (
    <TooltipProvider delayDuration={0}>
      <div className={cn("min-h-screen transition-colors duration-300", isDark ? "bg-slate-900" : "bg-slate-50")}>
        {/* Mobile sidebar backdrop */}
        {sidebarOpen && (
          <div 
            className="fixed inset-0 bg-black/50 z-40 lg:hidden"
            onClick={() => setSidebarOpen(false)}
          />
        )}

        {/* Sidebar */}
        <aside 
          className={cn(
            "fixed top-0 left-0 z-50 h-full border-r transform transition-all duration-300 lg:translate-x-0",
            isDark ? "bg-slate-800 border-slate-700" : "bg-white border-slate-200",
            sidebarOpen ? "translate-x-0" : "-translate-x-full",
            sidebarCollapsed ? "lg:w-20" : "lg:w-64",
            "w-64"
          )}
        >
          <div className="flex flex-col h-full">
            {/* Logo */}
            <div className={cn(
              "flex items-center h-16 px-4 border-b",
              isDark ? "border-slate-700" : "border-slate-200",
              sidebarCollapsed ? "lg:justify-center" : "justify-between"
            )}>
              <Link to="/dashboard" className="flex items-center gap-3">
                <div className={cn(
                  "w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0",
                  isDark && "bg-indigo-500"
                )}>
                  <Camera className="w-5 h-5 text-white" />
                </div>
                <span className={cn(
                  "text-lg font-bold transition-opacity duration-200",
                  isDark ? "text-white" : "text-slate-900",
                  sidebarCollapsed ? "lg:hidden" : ""
                )}>
                  SmartAttend
                </span>
              </Link>
              
              {/* Mobile close button */}
              <button 
                onClick={() => setSidebarOpen(false)}
                className={cn(
                  "lg:hidden p-1 rounded-md",
                  isDark ? "text-slate-400 hover:text-white hover:bg-slate-700" : "text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                )}
                aria-label="Close sidebar"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* Collapse Toggle Button - Desktop only */}
            <div className={cn(
              "hidden lg:flex items-center px-4 py-2 border-b",
              isDark ? "border-slate-700" : "border-slate-200",
              sidebarCollapsed ? "justify-center" : "justify-end"
            )}>
              <Tooltip>
                <TooltipTrigger asChild>
                  <button
                    onClick={toggleSidebarCollapse}
                    className={cn(
                      "p-2 rounded-lg transition-colors",
                      isDark 
                        ? "text-slate-400 hover:text-white hover:bg-slate-700" 
                        : "text-slate-500 hover:text-slate-900 hover:bg-slate-100"
                    )}
                    aria-label={sidebarCollapsed ? "Expand sidebar" : "Collapse sidebar"}
                    data-testid="sidebar-toggle-btn"
                  >
                    {sidebarCollapsed ? (
                      <PanelLeft className="w-5 h-5" />
                    ) : (
                      <PanelLeftClose className="w-5 h-5" />
                    )}
                  </button>
                </TooltipTrigger>
                <TooltipContent side="right">
                  {sidebarCollapsed ? "Expand sidebar" : "Collapse sidebar"}
                </TooltipContent>
              </Tooltip>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
              {filteredNavigation.map((item) => {
                const isActive = location.pathname === item.href || 
                  (item.href !== '/dashboard' && location.pathname.startsWith(item.href));
                
                const NavLink = (
                  <Link
                    key={item.name}
                    to={item.href}
                    onClick={() => setSidebarOpen(false)}
                    className={cn(
                      "flex items-center gap-3 px-3 py-3 rounded-lg font-medium transition-all",
                      sidebarCollapsed && "lg:justify-center lg:px-2",
                      isActive 
                        ? isDark 
                          ? "bg-indigo-600/20 text-indigo-400" 
                          : "bg-indigo-50 text-indigo-700"
                        : isDark
                          ? "text-slate-300 hover:bg-slate-700 hover:text-white"
                          : "text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                    )}
                    data-testid={`nav-${item.name.toLowerCase()}`}
                    aria-current={isActive ? "page" : undefined}
                  >
                    <item.icon className={cn("w-5 h-5 flex-shrink-0", isActive && (isDark ? "text-indigo-400" : "text-indigo-600"))} />
                    <span className={cn(
                      "transition-opacity duration-200",
                      sidebarCollapsed ? "lg:hidden" : ""
                    )}>
                      {item.name}
                    </span>
                  </Link>
                );

                // Wrap with tooltip when collapsed
                if (sidebarCollapsed) {
                  return (
                    <Tooltip key={item.name}>
                      <TooltipTrigger asChild>
                        {NavLink}
                      </TooltipTrigger>
                      <TooltipContent side="right" className="lg:block hidden">
                        {item.name}
                      </TooltipContent>
                    </Tooltip>
                  );
                }

                return NavLink;
              })}
            </nav>

            {/* Theme Toggle */}
            <div className={cn(
              "px-4 py-3 border-t",
              isDark ? "border-slate-700" : "border-slate-200"
            )}>
              <div className={cn(
                "flex items-center gap-3",
                sidebarCollapsed ? "lg:justify-center" : "justify-between"
              )}>
                {!sidebarCollapsed && (
                  <div className="flex items-center gap-2">
                    <Sun className={cn("w-4 h-4", isDark ? "text-slate-500" : "text-amber-500")} />
                    <span className={cn(
                      "text-sm font-medium",
                      isDark ? "text-slate-400" : "text-slate-600"
                    )}>
                      {isDark ? 'Dark' : 'Light'}
                    </span>
                    <Moon className={cn("w-4 h-4", isDark ? "text-indigo-400" : "text-slate-400")} />
                  </div>
                )}
                
                <Tooltip>
                  <TooltipTrigger asChild>
                    <button
                      onClick={toggleTheme}
                      className={cn(
                        "relative inline-flex h-8 w-14 items-center rounded-full transition-colors",
                        isDark ? "bg-indigo-600" : "bg-slate-200"
                      )}
                      role="switch"
                      aria-checked={isDark}
                      aria-label={`Switch to ${isDark ? 'light' : 'dark'} mode`}
                      data-testid="theme-toggle-btn"
                    >
                      <span
                        className={cn(
                          "inline-flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-sm transition-transform",
                          isDark ? "translate-x-7" : "translate-x-1"
                        )}
                      >
                        {isDark ? (
                          <Moon className="w-3.5 h-3.5 text-indigo-600" />
                        ) : (
                          <Sun className="w-3.5 h-3.5 text-amber-500" />
                        )}
                      </span>
                    </button>
                  </TooltipTrigger>
                  <TooltipContent side={sidebarCollapsed ? "right" : "top"}>
                    Switch to {isDark ? 'light' : 'dark'} mode
                  </TooltipContent>
                </Tooltip>
              </div>
            </div>

            {/* Face Registration CTA - Only for students without face registered */}
            {user?.role === 'student' && !user?.face_registered && !sidebarCollapsed && (
              <div className="px-4 pb-4">
                <Link to="/dashboard/face-registration">
                  <div className={cn(
                    "rounded-xl p-4 text-white",
                    isDark 
                      ? "bg-gradient-to-r from-indigo-600 to-indigo-700" 
                      : "bg-gradient-to-r from-indigo-600 to-indigo-700"
                  )}>
                    <p className="font-medium mb-1">Register Your Face</p>
                    <p className="text-sm text-indigo-100">Required for attendance</p>
                    <ChevronRight className="w-5 h-5 mt-2" />
                  </div>
                </Link>
              </div>
            )}

            {/* User section */}
            <div className={cn(
              "p-4 border-t",
              isDark ? "border-slate-700" : "border-slate-200"
            )}>
              <div className={cn(
                "flex items-center gap-3",
                sidebarCollapsed && "lg:justify-center"
              )}>
                <Avatar className="h-10 w-10 flex-shrink-0">
                  <AvatarFallback className={cn(
                    "font-medium",
                    isDark ? "bg-indigo-600 text-white" : "bg-indigo-100 text-indigo-700"
                  )}>
                    {getInitials(user?.name)}
                  </AvatarFallback>
                </Avatar>
                <div className={cn(
                  "flex-1 min-w-0",
                  sidebarCollapsed ? "lg:hidden" : ""
                )}>
                  <p className={cn(
                    "text-sm font-medium truncate",
                    isDark ? "text-white" : "text-slate-900"
                  )}>{user?.name}</p>
                  <p className={cn(
                    "text-xs capitalize",
                    isDark ? "text-slate-400" : "text-slate-500"
                  )}>{user?.role}</p>
                </div>
              </div>
            </div>
          </div>
        </aside>

        {/* Main content */}
        <div className={cn(
          "transition-all duration-300",
          sidebarCollapsed ? "lg:pl-20" : "lg:pl-64"
        )}>
          {/* Top header */}
          <header className={cn(
            "sticky top-0 z-30 flex items-center justify-between h-16 px-6 border-b backdrop-blur-sm",
            isDark 
              ? "bg-slate-900/95 border-slate-700" 
              : "bg-white/95 border-slate-200"
          )}>
            <button 
              onClick={() => setSidebarOpen(true)}
              className={cn(
                "lg:hidden p-2 rounded-lg",
                isDark 
                  ? "text-slate-300 hover:text-white hover:bg-slate-700" 
                  : "text-slate-600 hover:text-slate-900 hover:bg-slate-100"
              )}
              aria-label="Open sidebar"
              data-testid="mobile-menu-btn"
            >
              <Menu className="w-6 h-6" />
            </button>

            <div className="flex-1 lg:ml-0" />

            {/* Header Theme Toggle - Mobile */}
            <div className="lg:hidden mr-2">
              <Tooltip>
                <TooltipTrigger asChild>
                  <button
                    onClick={toggleTheme}
                    className={cn(
                      "p-2 rounded-lg transition-colors",
                      isDark 
                        ? "text-slate-300 hover:text-white hover:bg-slate-700" 
                        : "text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                    )}
                    aria-label={`Switch to ${isDark ? 'light' : 'dark'} mode`}
                  >
                    {isDark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                  </button>
                </TooltipTrigger>
                <TooltipContent>
                  Switch to {isDark ? 'light' : 'dark'} mode
                </TooltipContent>
              </Tooltip>
            </div>

            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button 
                  variant="ghost" 
                  className={cn(
                    "flex items-center gap-2",
                    isDark && "hover:bg-slate-700"
                  )} 
                  data-testid="user-menu-btn"
                >
                  <Avatar className="h-8 w-8">
                    <AvatarFallback className={cn(
                      "text-sm font-medium",
                      isDark ? "bg-indigo-600 text-white" : "bg-indigo-100 text-indigo-700"
                    )}>
                      {getInitials(user?.name)}
                    </AvatarFallback>
                  </Avatar>
                  <span className={cn(
                    "hidden sm:inline text-sm font-medium",
                    isDark ? "text-white" : "text-slate-900"
                  )}>{user?.name}</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className={cn(
                "w-56",
                isDark && "bg-slate-800 border-slate-700"
              )}>
                <DropdownMenuLabel className={isDark ? "text-slate-200" : ""}>
                  My Account
                </DropdownMenuLabel>
                <DropdownMenuSeparator className={isDark ? "bg-slate-700" : ""} />
                <DropdownMenuItem 
                  onClick={() => navigate('/dashboard/settings')}
                  className={isDark ? "text-slate-200 focus:bg-slate-700" : ""}
                >
                  <User className="w-4 h-4 mr-2" />
                  Profile
                </DropdownMenuItem>
                <DropdownMenuItem 
                  onClick={() => navigate('/dashboard/settings')}
                  className={isDark ? "text-slate-200 focus:bg-slate-700" : ""}
                >
                  <Settings className="w-4 h-4 mr-2" />
                  Settings
                </DropdownMenuItem>
                <DropdownMenuSeparator className={isDark ? "bg-slate-700" : ""} />
                <DropdownMenuItem 
                  onClick={handleLogout} 
                  className="text-red-600 focus:text-red-600" 
                  data-testid="logout-btn"
                >
                  <LogOut className="w-4 h-4 mr-2" />
                  Log out
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </header>

          {/* Page content */}
          <main className={cn(
            "p-6 min-h-[calc(100vh-4rem)]",
            isDark ? "bg-slate-900" : "bg-slate-50"
          )}>
            <Outlet />
          </main>
        </div>

        {/* Mobile bottom navigation */}
        <nav className={cn(
          "fixed bottom-0 left-0 right-0 z-40 border-t lg:hidden",
          isDark ? "bg-slate-800 border-slate-700" : "bg-white border-slate-200"
        )}>
          <div className="flex items-center justify-around h-16 px-2">
            {filteredNavigation.slice(0, 5).map((item) => {
              const isActive = location.pathname === item.href || 
                (item.href !== '/dashboard' && location.pathname.startsWith(item.href));
              
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={cn(
                    "flex flex-col items-center gap-1 px-3 py-2 rounded-lg transition-colors",
                    isActive 
                      ? isDark ? "text-indigo-400" : "text-indigo-700"
                      : isDark ? "text-slate-400" : "text-slate-400"
                  )}
                  aria-current={isActive ? "page" : undefined}
                >
                  <item.icon className="w-5 h-5" />
                  <span className="text-xs font-medium">{item.name}</span>
                </Link>
              );
            })}
          </div>
        </nav>
      </div>
    </TooltipProvider>
  );
}
