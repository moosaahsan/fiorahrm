@props(['events'])

@php
use Carbon\Carbon;
@endphp

<div class="col-xl-4">
    <div class="card">
        <div class="card-body no-padding" style="padding-top:0px; padding-bottom:0px;">
            <div class="bg-blink p-2 pt-3">
                <h4 class="mt-0 header-title text-white">
                    <i class="fa fa-calendar mx-2" aria-hidden="true"></i>Upcoming Events
                </h4>
            </div>
            <div class="p-2 px-4">
                <ul class="list-unstyled rec-acti-list">
                    <div class="wrap">
                        @if (!empty($events) && count($events))
                            <div class="slider">
                                <div class="slider__row" id="upcoming_events">
                                    @foreach ($events as $event)
                                        <div class="row__item">
                                            <div class="event-card row rec-acti-list-item">
                                                <div class="col-md-3 d-flex align-items-center justify-content-center">
                                                    <i class="fa fa-calendar" style="font-size: 50px;" aria-hidden="true"></i>
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="event-title">{{ $event->title }}</div>
                                                    <div class="event-date text-primary text-muted">
                                                        Date: <span class="text-danger">{{ Carbon::parse($event->event_held_on)->format('l, d F') }}</span>
                                                    </div>
                                                    @if($event->event_type)
                                                        <span class="event-badge event-badge-closed">Office Will Remain Closed</span>
                                                    @else
                                                        <span class="event-badge event-badge-open">Office Will Remain Open</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <li class="row rec-acti-list-item text-center">
                                <div class="col-md-12">
                                    <p class="text-primary text-muted mb-1">No Events Available</p>
                                </div>
                            </li>
                        @endif
                    </div>
                </ul>
            </div>
        </div>
    </div>
</div>
