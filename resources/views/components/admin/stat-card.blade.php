@props(['icon', 'title', 'value', 'route', 'variant' => 'primary'])

@php
    $gradients = [
        // Professional HRM Palette (Clean, Solid/Subtle Gradient)
        'primary' => 'linear-gradient(135deg, #4338ca 0%, #3730a3 100%)',   // Indigo-700 to Indigo-800 check (Total Employees) - Trusted/Corporate
        'success' => 'linear-gradient(135deg, #059669 0%, #047857 100%)',   // Emerald-600 to Emerald-700 (On Time) - Success/Growth
        'info'    => 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',   // Sky-600 to Sky-700 (Percentages) - Informative
        'danger'  => 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',   // Red-600 to Red-700 (Late) - Alert/Action needed
        'warning' => 'linear-gradient(135deg, #d97706 0%, #b45309 100%)',   // Amber-600 to Amber-700 (Half Day) - Cautionary
    ];
    $bgStyle = $gradients[$variant] ?? $gradients['primary'];
    // Softer shadow for elegance
    $shadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
@endphp

<div class="col-xl-3 col-md-6">
    <div class="card mini-stat text-white" style="background: {{ $bgStyle }}; border-radius: 12px; border: none; box-shadow: {{ $shadow }}; overflow: hidden; transition: transform 0.2s;">
        <div class="card-body" style="padding: 1.5rem;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                     <h5 class="font-14 text-uppercase mt-0 text-white-50" style="color: rgba(255,255,255,0.75) !important; letter-spacing: 0.5px; font-weight: 600;">{{ $title }}</h5>
                     <h4 class="font-500 mb-0" style="font-weight: 700; font-size: 32px; margin-top: 10px;">{{ $value }}</h4>
                </div>
                <div class="mini-stat-img">
                    <div style="background: rgba(255,255,255,0.15); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                        <i class="{{ $icon }}" style="font-size: 24px; color: #fff;"></i>
                    </div>
                </div>
            </div>
            
            <div class="pt-2 mt-2" style="border-top: 1px solid rgba(255,255,255,0.1);">
                <div class="float-right">
                    <a href="{{ $route }}" class="text-white-50" style="color: rgba(255,255,255,0.8) !important; font-size: 0.85rem; display: flex; align-items: center;">
                        View Details <i class="mdi mdi-arrow-right h5 ml-1 mb-0"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
