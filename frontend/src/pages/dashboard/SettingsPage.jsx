import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { usersAPI, settingsAPI, smsAPI } from '@/context/AuthContext';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { 
  User, 
  Bell, 
  Settings as SettingsIcon,
  Save,
  Loader2,
  MessageSquare,
  Clock
} from 'lucide-react';

export default function SettingsPage() {
  const { user, updateUser } = useAuth();
  const [loading, setLoading] = useState(false);
  const [smsStatus, setSmsStatus] = useState({ sms_enabled: false, provider: 'mocked' });
  const [profileData, setProfileData] = useState({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    parent_phone: user?.parent_phone || '',
    parent_email: user?.parent_email || ''
  });
  const [settings, setSettings] = useState({
    grace_period_minutes: 15,
    sms_notifications_enabled: true,
    late_threshold_minutes: 30
  });

  useEffect(() => {
    loadSettings();
    loadSmsStatus();
  }, []);

  const loadSettings = async () => {
    try {
      const response = await settingsAPI.get();
      setSettings(response.data);
    } catch (error) {
      console.error('Failed to load settings:', error);
    }
  };

  const loadSmsStatus = async () => {
    try {
      const response = await smsAPI.getStatus();
      setSmsStatus(response.data);
    } catch (error) {
      console.error('Failed to load SMS status:', error);
    }
  };

  const handleProfileUpdate = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await usersAPI.update(user.id, profileData);
      updateUser(profileData);
      toast.success('Profile updated successfully');
    } catch (error) {
      toast.error('Failed to update profile');
    } finally {
      setLoading(false);
    }
  };

  const handleSettingsUpdate = async () => {
    if (user?.role !== 'admin') return;
    setLoading(true);

    try {
      await settingsAPI.update(settings);
      toast.success('Settings updated successfully');
    } catch (error) {
      toast.error('Failed to update settings');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6 max-w-3xl" data-testid="settings-page">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Settings</h1>
        <p className="text-slate-600">Manage your account and preferences</p>
      </div>

      {/* Profile Settings */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <User className="w-5 h-5" />
            Profile Information
          </CardTitle>
          <CardDescription>Update your personal details</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleProfileUpdate} className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name">Full Name</Label>
                <Input
                  id="name"
                  value={profileData.name}
                  onChange={(e) => setProfileData({ ...profileData, name: e.target.value })}
                  data-testid="settings-name-input"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  type="email"
                  value={profileData.email}
                  disabled
                  className="bg-slate-50"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="phone">Phone Number</Label>
                <Input
                  id="phone"
                  type="tel"
                  placeholder="+1234567890"
                  value={profileData.phone}
                  onChange={(e) => setProfileData({ ...profileData, phone: e.target.value })}
                />
              </div>

              <div className="space-y-2">
                <Label>Role</Label>
                <Input
                  value={user?.role?.charAt(0).toUpperCase() + user?.role?.slice(1)}
                  disabled
                  className="bg-slate-50 capitalize"
                />
              </div>
            </div>

            {user?.role === 'student' && (
              <>
                <Separator className="my-4" />
                <p className="text-sm font-medium text-slate-700 mb-3">Parent/Guardian Contact</p>
                
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="parent_phone">Parent Phone</Label>
                    <Input
                      id="parent_phone"
                      type="tel"
                      placeholder="+1234567890"
                      value={profileData.parent_phone}
                      onChange={(e) => setProfileData({ ...profileData, parent_phone: e.target.value })}
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="parent_email">Parent Email</Label>
                    <Input
                      id="parent_email"
                      type="email"
                      placeholder="parent@email.com"
                      value={profileData.parent_email}
                      onChange={(e) => setProfileData({ ...profileData, parent_email: e.target.value })}
                    />
                  </div>
                </div>
              </>
            )}

            <div className="flex justify-end pt-4">
              <Button type="submit" disabled={loading} data-testid="save-profile-btn">
                {loading ? (
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                ) : (
                  <Save className="w-4 h-4 mr-2" />
                )}
                Save Changes
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      {/* SMS Status */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MessageSquare className="w-5 h-5" />
            SMS Notifications
          </CardTitle>
          <CardDescription>SMS notification system status</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
            <div>
              <p className="font-medium text-slate-900">
                {smsStatus.sms_enabled ? 'SMS Enabled' : 'SMS in Mock Mode'}
              </p>
              <p className="text-sm text-slate-500">
                Provider: {smsStatus.provider === 'twilio' ? 'Twilio' : 'Mock (logs only)'}
              </p>
            </div>
            <div className={`w-3 h-3 rounded-full ${smsStatus.sms_enabled ? 'bg-teal-500' : 'bg-amber-500'}`} />
          </div>
          
          {!smsStatus.sms_enabled && (
            <p className="text-sm text-slate-500 mt-3">
              To enable real SMS notifications, add Twilio credentials to the backend environment variables.
            </p>
          )}
        </CardContent>
      </Card>

      {/* Admin Settings */}
      {user?.role === 'admin' && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <SettingsIcon className="w-5 h-5" />
              System Settings
            </CardTitle>
            <CardDescription>Configure global attendance settings</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <Label className="text-base">SMS Notifications</Label>
                  <p className="text-sm text-slate-500">Send SMS to parents when attendance is marked</p>
                </div>
                <Switch
                  checked={settings.sms_notifications_enabled}
                  onCheckedChange={(checked) => setSettings({ ...settings, sms_notifications_enabled: checked })}
                />
              </div>

              <Separator />

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label className="flex items-center gap-2">
                    <Clock className="w-4 h-4" />
                    Grace Period (minutes)
                  </Label>
                  <Input
                    type="number"
                    min="0"
                    max="60"
                    value={settings.grace_period_minutes}
                    onChange={(e) => setSettings({ ...settings, grace_period_minutes: parseInt(e.target.value) })}
                  />
                  <p className="text-xs text-slate-500">Default grace period before marking as late</p>
                </div>

                <div className="space-y-2">
                  <Label className="flex items-center gap-2">
                    <Clock className="w-4 h-4" />
                    Late Threshold (minutes)
                  </Label>
                  <Input
                    type="number"
                    min="0"
                    max="120"
                    value={settings.late_threshold_minutes}
                    onChange={(e) => setSettings({ ...settings, late_threshold_minutes: parseInt(e.target.value) })}
                  />
                  <p className="text-xs text-slate-500">Maximum late time before marking as absent</p>
                </div>
              </div>

              <div className="flex justify-end pt-4">
                <Button onClick={handleSettingsUpdate} disabled={loading}>
                  {loading ? (
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  ) : (
                    <Save className="w-4 h-4 mr-2" />
                  )}
                  Save Settings
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
