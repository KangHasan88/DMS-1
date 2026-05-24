@extends('layouts.sidebar')

@section('page-title', 'Detail Activity Log')
@section('breadcrumb', 'System / Activity Log / Detail')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Detail Activity Log</h3>
            <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Informasi lengkap aktivitas</p>
        </div>
        <a href="{{ route('activity-logs.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
        <!-- Basic Information -->
        <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem;">Informasi Dasar</h4>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600); width: 120px;">ID Log</td>
                    <td style="padding: 0.5rem 0;">: <code>{{ $activityLog->id }}</code></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600);">Log Name</td>
                    <td style="padding: 0.5rem 0;">: <span class="dms-badge dms-badge-info">{{ $activityLog->log_name }}</span></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600);">Event</td>
                    <td style="padding: 0.5rem 0;">: 
                        @php
                            $eventClass = match($activityLog->event) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                'login' => 'primary',
                                'logout' => 'secondary',
                                default => 'info'
                            };
                        @endphp
                        <span class="dms-badge dms-badge-{{ $eventClass }}">{{ $activityLog->event ?? 'system' }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600);">Waktu</td>
                    <td style="padding: 0.5rem 0;">: {{ $activityLog->created_at->format('d M Y H:i:s') }}</td>
                </tr>
            </table>
        </div>

        <!-- User Information -->
        <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem;">Informasi User</h4>
            
            @if($activityLog->causer)
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; background: var(--dms-primary-light); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-person-circle" style="font-size: 2rem; color: var(--dms-primary);"></i>
                </div>
                <div>
                    <div style="font-size: 1.1rem; font-weight: 600;">{{ $activityLog->causer->name }}</div>
                    <div style="color: var(--dms-gray-500);">{{ $activityLog->causer->email }}</div>
                    <div style="margin-top: 0.5rem;">
                        @foreach($activityLog->causer->roles as $role)
                            <span class="dms-badge dms-badge-info">{{ ucwords(str_replace('-', ' ', $role->name)) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div style="text-align: center; padding: 1rem;">
                <i class="bi bi-robot" style="font-size: 2rem; color: var(--dms-gray-400);"></i>
                <p style="margin-top: 0.5rem;">System / Cron Job</p>
            </div>
            @endif

            <table style="width: 100%; margin-top: 1rem;">
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600);">IP Address</td>
                    <td style="padding: 0.5rem 0;">: <code>{{ $activityLog->ip_address ?? '-' }}</code></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600);">User Agent</td>
                    <td style="padding: 0.5rem 0;">: <span style="font-size: 0.8rem;">{{ $activityLog->user_agent ?? '-' }}</span></td>
                </tr>
            </table>
        </div>

        <!-- Subject Information -->
        @if($activityLog->subject)
        <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem;">Subject Terkait</h4>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600);">Type</td>
                    <td style="padding: 0.5rem 0;">: <span class="dms-badge dms-badge-info">{{ class_basename($activityLog->subject_type) }}</span></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600);">ID</td>
                    <td style="padding: 0.5rem 0;">: <code>{{ $activityLog->subject_id }}</code></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: var(--dms-gray-600);">Deskripsi</td>
                    <td style="padding: 0.5rem 0;">: {{ $activityLog->description }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Properties / Data Changes -->
        @if($activityLog->properties && $activityLog->properties->count())
        <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200); grid-column: span 2;">
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem;">Data Properties</h4>
            
            @if(isset($activityLog->properties['attributes']))
                <!-- Old Attributes -->
                @if(isset($activityLog->properties['old']))
                <div style="margin-bottom: 2rem;">
                    <h5 style="font-size: 0.9rem; color: var(--dms-warning); margin-bottom: 1rem;">Data Lama</h5>
                    <div style="background: #fff3e0; border-radius: 8px; padding: 1rem;">
                        <pre style="margin: 0; font-size: 0.8rem; white-space: pre-wrap;">{{ json_encode($activityLog->properties['old'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
                @endif
                
                <!-- New Attributes -->
                <div>
                    <h5 style="font-size: 0.9rem; color: var(--dms-success); margin-bottom: 1rem;">Data Baru</h5>
                    <div style="background: #e6f7e6; border-radius: 8px; padding: 1rem;">
                        <pre style="margin: 0; font-size: 0.8rem; white-space: pre-wrap;">{{ json_encode($activityLog->properties['attributes'] ?? $activityLog->properties, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @else
                <div style="background: var(--dms-gray-100); border-radius: 8px; padding: 1rem;">
                    <pre style="margin: 0; font-size: 0.8rem; white-space: pre-wrap;">{{ json_encode($activityLog->properties, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection