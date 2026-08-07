@props(['birthdays'])

<div class="col-xl-4">
    <div class="card">
        <div class="card-body no-padding" style="padding-top:0px; padding-bottom:0px;">
            <div class="bg-primary p-2 pt-3">
                <h4 class="mt-0 header-title text-white">
                    <i class="fa fa-birthday-cake mx-2 events-icon" aria-hidden="true"></i>
                    Upcoming Birthdays
                </h4>
            </div>
            <div class="p-2 px-4">
                <ul class="list-unstyled rec-acti-list">
                    @if (!empty($birthdays) && count($birthdays))
                        @foreach ($birthdays as $person)
                            <li class="row rec-acti-list-item">
                                <div class="col-md-3 d-flex align-items-center justify-content-center">
                                    @if ($person->profile_pic)
                                        <img src="{{ asset('storage/assets/profile_pics/' . $person->profile_pic) }}"
                                            class="mx-2 rounded-circle text-center"
                                            style="width: 60px; height: 60px; object-fit: cover;" alt="Profile" />
                                    @else
                                        <img src="{{ asset('assets/images/profile1.png') }}"
                                            class="mx-2 rounded-circle text-center"
                                            style="width: 60px; height: 60px; object-fit: cover;" alt="Default" />
                                    @endif
                                </div>
                                <div class="col-md-9">
                                    <h6 class="mb-0">
                                        <a href="#" class="text-dark">{{ $person->name }}</a>
                                    </h6>
                                    <p class="text-primary text-muted mb-1">
                                        Date:
                                        <span class="text-danger">{{ formatBirthday($person->dob) }}</span>
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    @else
                        <li class="row rec-acti-list-item">
                            <div class="col-md-12">
                                <p class="text-primary text-muted mb-1">No Birthdays This Week</p>
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
