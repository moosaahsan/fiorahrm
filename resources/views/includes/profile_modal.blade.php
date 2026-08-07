<div class="modal fade" id="userProfile">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><b>Profile</b></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
            </div>
            @php
                $emp_id = get_emp_id(auth()->user()->id);
                $employee = employee_details($emp_id);
            @endphp
            <div class="modal-body">
                <div class="card" style="border-radius: 15px;">
                    <div class="card-body text-center">
                        <h4 class="mb-2">{{ $employee->name }}</h4>
                        <p class="text-muted mb-4">{{ $employee->position }} <span class="mx-2">|</span> <a
                                href="#!">
                                @if (!$employee->joining_date == '')
                                    {{ date('d-m-Y', strtotime($employee->joining_date)) }}
                                @else
                                    {{ date('d-m-Y', strtotime($employee->created_at)) }}
                                @endif
                            </a></p>
                        <div class="row">
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-sm-5">
                                        <h6 class="mt-0 text-left">Email</h6>
                                    </div>
                                    <div class="col-sm-7 text-secondary text-left">
                                        <span>{{ $employee->email }}</span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-5">
                                        <h6 class="mt-0 text-left">Joining Date</h6>
                                    </div>
                                    <div class="col-sm-7 text-secondary text-left">
                                        <span>
                                            @if (!$employee->joining_date == '')
                                                {{ date('d-m-Y', strtotime($employee->joining_date)) }}
                                            @else
                                                {{ date('d-m-Y', strtotime($employee->created_at)) }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-5">
                                        <h6 class="mt-0 text-left">Contact No</h6>
                                    </div>
                                    <div class="col-sm-7 text-secondary text-left">
                                        <span>{{ $employee->contact_no }}</span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-5">
                                        <h6 class="mt-0 text-left">Emerg No</h6>
                                    </div>
                                    <div class="col-sm-7 text-secondary text-left">
                                        <span>{{ $employee->emergency_no }}</span>
                                    </div>
                                </div>
                                <div class="row">
                                    @php
                                        $probation_comp = strtotime('+' . $employee->probation . 'months', strtotime($employee->joining_date));
                                        $today = strtotime(date('d-m-Y'));
                                    @endphp
                                    <div class="col-sm-5">
                                        <h6 class="mt-0 text-left">Employem..</h6>
                                    </div>
                                    <div class="col-sm-7 text-secondary text-left">
                                        @if (strtotime('+' . $employee->probation . 'months', strtotime($employee->joining_date)) > strtotime(date('d-m-Y')))
                                        <span class="text-info">In Probation</span>
                                            {{-- <p>Probation Complete {{Date('d-m-Y', $probation_comp)}} </p> --}}
                                        @endif
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-5">
                                        <h6 class="mt-0 text-left">Probation Complete</h6>
                                    </div>
                                    <div class="col-sm-7 text-secondary text-left">
                                        <span>{{ date('d-m-Y', $probation_comp) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between text-center mt-5 mb-2">
                            <div>
                                <p class="mb-2 h5">{{ get_workingHour_monthly($emp_id, Date('m')) }}</p>
                                <p class="text-muted mb-0">This Month</p>
                            </div>
                            <div class="px-3">
                                <p class="mb-2 h5">
                                    {{ get_workingHour_monthly($emp_id, Date('m', strtotime('-1 month'))) }}</p>
                                <p class="text-muted mb-0">Last Month</p>
                            </div>
                            <div>
                                <p class="mb-2 h5">{{ get_thisweek_hour($emp_id) }}</p>
                                <p class="text-muted mb-0">This week</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
