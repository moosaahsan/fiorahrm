@props(['employees'])

<div class="col-xl-4">
    <div class="card">
        <div class="card-body no-padding" style="padding-top:0px; padding-bottom:0px;">
            <div class="bg-primary p-2 pt-3">
                <h4 class="mt-0 header-title text-white">In Probation</h4>
            </div>
            <div class="p-2 px-4">
                <ul class="list-unstyled rec-acti-list">
                    @foreach ($employees as $employee)
                        @php
                            $probationEnd = strtotime('+' . $employee->probation . ' months', strtotime($employee->joining_date));
                            $today = strtotime(now()->format('d-m-Y'));
                        @endphp
                        @if ($probationEnd >= $today)
                            <li class="row rec-acti-list-item">
                                <div class="col-md-1 d-flex align-items-center justify-content-center">
                                    <span class="ti-alarm-clock" style="font-size: 30px"></span>
                                </div>
                                <div class="col-md-10">
                                    <h6 class="mb-0">
                                        Employee Name:
                                        <a href="javascript:void(0)" class="text-dark">{{ $employee->name }}</a>
                                    </h6>
                                    <p class="text-primary text-muted mb-1">
                                        Probation Complete on:
                                        <span class="text-danger">{{ date('d-m-Y', $probationEnd) }}</span>
                                    </p>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
