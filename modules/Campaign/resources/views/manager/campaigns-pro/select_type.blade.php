@extends('campaign::refactor.layout')

@section('title', trans('campaign::campaigns.wizard.select_type'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::campaigns.wizard.select_type') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::campaigns.wizard.select_type_desc') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.campaigns_pro.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::campaigns.wizard.all_campaigns') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div style="max-width:740px;margin:0 auto">

        <div class="mc-action-rows">
            <div class="mc-action-rows-header">
                <span class="mc-action-rows-title">
                    <span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:var(--space-1-5)">bolt</span>
                    {{ trans('campaign::campaigns.wizard.select_type_desc') }}
                </span>
            </div>

            {{-- ── Regular Campaign ─────────────────────────────────────────── --}}
            <div class="mc-action-row">
                <div class="mc-action-row-thumb" style="background:var(--chart-1-bg)">
                    <svg viewBox="0 0 64 52" fill="none" width="64" height="52" xmlns="http://www.w3.org/2000/svg">
                        <rect x="8" y="4" width="48" height="44" rx="3.5" fill="white" stroke="var(--chart-1)" stroke-width="1.2"/>
                        <rect x="8" y="4" width="48" height="13" rx="3.5" fill="var(--chart-1)" opacity="0.62"/>
                        <rect x="8" y="13" width="48" height="4" fill="var(--chart-1)" opacity="0.62"/>
                        <rect x="21" y="7.5" width="22" height="3" rx="1.5" fill="white" opacity="0.82"/>
                        <rect x="14" y="22" width="36" height="7" rx="2" fill="var(--chart-1)" opacity="0.13"/>
                        <path d="M14 28.5 L21 24.5 L27 27.5 L34 22.5 L44 28.5" stroke="var(--chart-1)" stroke-width="1.2" stroke-linejoin="round" fill="none" opacity="0.45"/>
                        <rect x="14" y="33" width="36" height="2.5" rx="1.25" fill="var(--chart-1)" opacity="0.52"/>
                        <rect x="14" y="37.5" width="27" height="2" rx="1" fill="var(--chart-1)" opacity="0.36"/>
                        <rect x="19" y="41.5" width="26" height="6" rx="3" fill="var(--chart-1)" opacity="0.72"/>
                        <rect x="25" y="43.5" width="14" height="2" rx="1" fill="white" opacity="0.88"/>
                    </svg>
                </div>
                <div class="mc-action-row-content">
                    <div class="mc-action-row-title">
                        {{ trans('campaign::campaigns.wizard.type_regular') }}
                        <span class="mc-badge mc-badge-teal" style="margin-left:var(--space-2);vertical-align:2px">{{ trans('campaign::campaigns.wizard.type_most_popular') }}</span>
                    </div>
                    <div class="mc-action-row-desc">{{ trans('campaign::campaigns.wizard.type_regular_detail') }}</div>
                    <div style="margin-top:var(--space-1-5);font-size:var(--text-xs);color:var(--color-text-disabled)">
                        <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;opacity:0.7">star</span>
                        {{ trans('campaign::campaigns.wizard.type_regular_best') }}
                    </div>
                </div>
                <form method="POST" action="{{ route('manager.campaigns_pro.create') }}" style="flex-shrink:0">
                    @csrf
                    <input type="hidden" name="type" value="regular">
                    <button type="submit" class="mc-btn mc-btn-primary mc-btn-sm" style="white-space:nowrap">
                        {{ trans('campaign::campaigns.wizard.type_btn_create') }}
                    </button>
                </form>
            </div>

            {{-- ── Plain Text Campaign ──────────────────────────────────────── --}}
            <div class="mc-action-row">
                <div class="mc-action-row-thumb" style="background:var(--chart-3-bg)">
                    <svg viewBox="0 0 64 52" fill="none" width="64" height="52" xmlns="http://www.w3.org/2000/svg">
                        <rect x="13" y="3" width="38" height="46" rx="3" fill="white" stroke="var(--chart-3)" stroke-width="1.3"/>
                        <path d="M40 3 L51 14 L40 14 Z" fill="var(--chart-3-bg)" stroke="var(--chart-3)" stroke-width="1" stroke-linejoin="round"/>
                        <rect x="19" y="20" width="26" height="2.5" rx="1.25" fill="var(--chart-3)" opacity="0.72"/>
                        <rect x="19" y="25.5" width="22" height="2.5" rx="1.25" fill="var(--chart-3)" opacity="0.58"/>
                        <rect x="19" y="31" width="25" height="2.5" rx="1.25" fill="var(--chart-3)" opacity="0.46"/>
                        <rect x="19" y="36.5" width="17" height="2.5" rx="1.25" fill="var(--chart-3)" opacity="0.36"/>
                        <rect x="38" y="35" width="2" height="5" rx="0.5" fill="var(--chart-3)" opacity="0.82"/>
                    </svg>
                </div>
                <div class="mc-action-row-content">
                    <div class="mc-action-row-title">{{ trans('campaign::campaigns.wizard.type_plain') }}</div>
                    <div class="mc-action-row-desc">{{ trans('campaign::campaigns.wizard.type_plain_detail') }}</div>
                    <div style="margin-top:var(--space-1-5);font-size:var(--text-xs);color:var(--color-text-disabled)">
                        <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;opacity:0.7">star</span>
                        {{ trans('campaign::campaigns.wizard.type_plain_best') }}
                    </div>
                </div>
                <form method="POST" action="{{ route('manager.campaigns_pro.create') }}" style="flex-shrink:0">
                    @csrf
                    <input type="hidden" name="type" value="plain-text">
                    <button type="submit" class="mc-btn mc-btn-secondary mc-btn-sm" style="white-space:nowrap">
                        {{ trans('campaign::campaigns.wizard.type_btn_create') }}
                    </button>
                </form>
            </div>

            {{-- ── A/B Test Campaign (el backend la trata como regular por ahora) ── --}}
            <div class="mc-action-row">
                <div class="mc-action-row-thumb" style="background:var(--chart-6-bg)">
                    <svg viewBox="0 0 64 52" fill="none" width="64" height="52" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="6" width="25" height="35" rx="2.5" fill="white" stroke="var(--chart-6)" stroke-width="1.3"/>
                        <rect x="3" y="6" width="25" height="10" rx="2.5" fill="var(--chart-6)" opacity="0.65"/>
                        <rect x="3" y="12" width="25" height="4" fill="var(--chart-6)" opacity="0.65"/>
                        <rect x="7" y="23" width="17" height="2" rx="1" fill="var(--chart-6)" opacity="0.55"/>
                        <rect x="7" y="27" width="13" height="2" rx="1" fill="var(--chart-6)" opacity="0.4"/>
                        <rect x="9" y="32" width="13" height="5.5" rx="2.75" fill="var(--chart-6)" opacity="0.62"/>
                        <text x="15.5" y="50" text-anchor="middle" fill="var(--chart-6)" font-size="8" font-weight="700" font-family="system-ui,sans-serif" opacity="0.85">A</text>
                        <line x1="32" y1="14" x2="32" y2="34" stroke="var(--chart-6)" stroke-width="1" stroke-dasharray="2,2" opacity="0.45"/>
                        <text x="32" y="27" text-anchor="middle" dominant-baseline="middle" fill="var(--chart-6)" font-size="6.5" font-weight="600" font-family="system-ui,sans-serif" opacity="0.6">vs</text>
                        <rect x="36" y="6" width="25" height="35" rx="2.5" fill="white" stroke="var(--chart-6)" stroke-width="1.3" opacity="0.78"/>
                        <rect x="36" y="6" width="25" height="7" rx="2.5" fill="var(--chart-6)" opacity="0.38"/>
                        <rect x="36" y="10" width="25" height="3" fill="var(--chart-6)" opacity="0.38"/>
                        <rect x="40" y="23" width="17" height="2" rx="1" fill="var(--chart-6)" opacity="0.36"/>
                        <rect x="40" y="27" width="13" height="2" rx="1" fill="var(--chart-6)" opacity="0.28"/>
                        <rect x="42" y="32" width="13" height="5.5" rx="2.75" fill="var(--chart-6)" opacity="0.38"/>
                        <text x="48.5" y="50" text-anchor="middle" fill="var(--chart-6)" font-size="8" font-weight="700" font-family="system-ui,sans-serif" opacity="0.62">B</text>
                    </svg>
                </div>
                <div class="mc-action-row-content">
                    <div class="mc-action-row-title">
                        {{ trans('campaign::campaigns.wizard.type_ab') }}
                        <span class="mc-badge" style="margin-left:var(--space-2);vertical-align:2px;background:var(--chart-6-bg);color:var(--chart-6);border:1px solid var(--chart-6)">{{ trans('campaign::campaigns.wizard.type_ab_new') }}</span>
                    </div>
                    <div class="mc-action-row-desc">{{ trans('campaign::campaigns.wizard.type_ab_detail') }}</div>
                    <div style="margin-top:var(--space-1-5);font-size:var(--text-xs);color:var(--color-text-disabled)">
                        <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;opacity:0.7">star</span>
                        {{ trans('campaign::campaigns.wizard.type_ab_best') }}
                    </div>
                </div>
                <form method="POST" action="{{ route('manager.campaigns_pro.create') }}" style="flex-shrink:0">
                    @csrf
                    <input type="hidden" name="type" value="ab-test">
                    <button type="submit" class="mc-btn mc-btn-secondary mc-btn-sm" style="white-space:nowrap">
                        {{ trans('campaign::campaigns.wizard.type_btn_create') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- ── Automation Section ────────────────────────────────────────── --}}
        <div class="mc-action-rows" style="margin-top:var(--space-6)">
            <div class="mc-action-rows-header">
                <span class="mc-action-rows-title">
                    <span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:var(--space-1-5)">auto_mode</span>
                    {{ trans('campaign::campaigns.wizard.type_automation') }}
                </span>
            </div>

            <div class="mc-action-row">
                <div class="mc-action-row-thumb" style="background:var(--chart-4-bg)">
                    <svg viewBox="0 0 64 52" fill="none" width="64" height="52" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="32" cy="24" r="9" fill="white" stroke="var(--chart-4)" stroke-width="1.3"/>
                        <circle cx="32" cy="24" r="4.5" fill="var(--chart-4)" opacity="0.55"/>
                        <rect x="30" y="12" width="4" height="5" rx="1" fill="var(--chart-4)" opacity="0.5"/>
                        <rect x="30" y="31" width="4" height="5" rx="1" fill="var(--chart-4)" opacity="0.5"/>
                        <rect x="20" y="22" width="5" height="4" rx="1" fill="var(--chart-4)" opacity="0.5"/>
                        <rect x="39" y="22" width="5" height="4" rx="1" fill="var(--chart-4)" opacity="0.5"/>
                        <line x1="24" y1="17" x2="14" y2="9" stroke="var(--chart-4)" stroke-width="1.2" stroke-linecap="round" opacity="0.6"/>
                        <line x1="40" y1="17" x2="50" y2="9" stroke="var(--chart-4)" stroke-width="1.2" stroke-linecap="round" opacity="0.6"/>
                        <line x1="24" y1="31" x2="14" y2="39" stroke="var(--chart-4)" stroke-width="1.2" stroke-linecap="round" opacity="0.6"/>
                        <line x1="40" y1="31" x2="50" y2="39" stroke="var(--chart-4)" stroke-width="1.2" stroke-linecap="round" opacity="0.6"/>
                        <circle cx="12" cy="8" r="3" fill="var(--chart-4)" opacity="0.35"/>
                        <circle cx="52" cy="8" r="3" fill="var(--chart-4)" opacity="0.35"/>
                        <circle cx="12" cy="40" r="3" fill="var(--chart-4)" opacity="0.35"/>
                        <circle cx="52" cy="40" r="3" fill="var(--chart-4)" opacity="0.35"/>
                        <rect x="9.5" y="6.5" width="5" height="3.5" rx="0.8" fill="white" opacity="0.8"/>
                        <rect x="49.5" y="6.5" width="5" height="3.5" rx="0.8" fill="white" opacity="0.8"/>
                        <rect x="9.5" y="38.5" width="5" height="3.5" rx="0.8" fill="white" opacity="0.8"/>
                        <rect x="49.5" y="38.5" width="5" height="3.5" rx="0.8" fill="white" opacity="0.8"/>
                    </svg>
                </div>
                <div class="mc-action-row-content">
                    <div class="mc-action-row-title">
                        {{ trans('campaign::campaigns.wizard.type_automation') }}
                    </div>
                    <div class="mc-action-row-desc">{{ trans('campaign::campaigns.wizard.type_automation_detail') }}</div>
                    <div style="margin-top:var(--space-1-5);font-size:var(--text-xs);color:var(--color-text-disabled)">
                        <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;opacity:0.7">star</span>
                        {{ trans('campaign::campaigns.wizard.type_automation_best') }}
                    </div>
                </div>
                <a href="{{ route('manager.flow_automations.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm" style="flex-shrink:0;white-space:nowrap">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-right', 'size' => 14])
                    {{ trans('campaign::campaigns.wizard.type_automation_btn') }}
                </a>
            </div>
        </div>

    </div>
@endsection
