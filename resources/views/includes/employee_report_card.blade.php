@php
$leaveinfo = get_leave_balance($emp_id, $syear);
$employeeSetting = get_employee_setting($emp_id);
@endphp
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-light">
                <span class="ti-write" style="font-size: 11px"></span> Employee Attendance Report
            </div>
            <div class="card-body">
                {{-- <div>
                    <h4 class="mt-0 header-title mb-4"></h4>
                </div> --}}
                <div class="wid-peity mb-2" style="padding-top:0px; padding-bottom:0px;">
                    <div class="row">
                        <div class="col-md-6 d-flex align-items-center">
                            <div>
                                <h6 class="text-dark">Worked Days</h6>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <h3 class="text-dark">{{totalWorkDays($emp_id, Date($month_id), Date($syear))}}</h3>
                        </div>
                    </div>
                </div>
                <div class="wid-peity mb-2" style="padding-top:0px; padding-bottom:0px;">
                    <div class="row">
                        <div class="col-md-6 d-flex align-items-center">
                            <div>
                                <h6 class="text-dark fs-5"> Late In Month</h6>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <h3 class="text-danger">{{ lateCount($emp_id, Date($month_id), Date($syear))}}</h3>
                        </div>
                    </div>
                </div>
                <div class="wid-peity mb-2" style="padding-top:0px; padding-bottom:0px;">
                    <div class="row">
                        <div class="col-md-6 d-flex align-items-center">
                            <div>
                                <h6 class="text-dark">Working Hour</h6>
                            </div>
                        </div>
                        <div class="col-md-6 text-right work-hours">
                            <h3 class="text-dark time-format ">{{ get_workingHour_monthly($emp_id, Date($month_id), Date($syear)) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="wid-peity mb-2" style="padding-top:0px; padding-bottom:0px;">
                    <div class="row">
                        <div class="col-md-6 d-flex align-items-center break-spent-time">
                            <div>
                                <h6 class="text-dark">Break Spent</h6>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <h3 class="text-dark">{{get_clockOutTime_monthly($emp_id, Date($month_id, Date($syear)))}}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-light">
                <span class="ti-write" style="font-size: 11px"></span> Employee Leave Report
            </div>
            <div class="card-body">
                {{-- <div>
                    <h4 class="mt-0 header-title mb-4"></h4>
                </div> --}}
                <div class="wid-peity mb-2" style="padding-top:0px; padding-bottom:0px;">
                    <div class="row">
                        <div class="col-md-6 d-flex align-items-center">
                            <div>
                                <h6 class="text-dark">Total Leaves</h6>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <h3 class="text-dark">{{$leaveinfo['total']}}</h3>
                        </div>
                    </div>
                </div>
                <div class="wid-peity mb-2" style="padding-top:0px; padding-bottom:0px;">
                    <div class="row">
                        <div class="col-md-6 d-flex align-items-center">
                            <div>
                                <h6 class="text-dark">Half Leaves</h6>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <h3 class="text-dark">{{$leaveinfo['half']}}</h3>
                        </div>
                    </div>
                </div>
                <div class="wid-peity mb-2" style="padding-top:0px; padding-bottom:0px;">
                    <div class="row">
                        <div class="col-md-6  d-flex align-items-center">
                            <div>
                                <h6 class="text-dark">Leave Balance</h6>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <h3 class="text-success">{{$leaveinfo['remaning']}}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-light">
                <span class="ti-write" style="font-size: 11px"></span> Employee Setting
            </div>
            <div class="card-body">
                {{-- <div>
                    <h4 class="mt-0 header-title mb-4"></h4>
                </div> --}}
                <div class="wid-peity mb-2" style="padding-top:0px; padding-bottom:0px;">
                    <div class="row">
                        <div class="col-sm-6 d-flex align-items-center">
                            <div>
                                <h6 class="text-dark">Clock Out Time</h6>
                            </div>
                        </div>
                        <div class="col-sm-4 text-right">
                            <h6 class="text-dark">{{round($employeeSetting['clockOut_time']/60)}} min</h6>
                        </div>
                        @can('permission', 'edit')
                        <div class="col-sm-1"><input id="edit21" onclick="showEditDiv('popup21', 'popup21a', 'popup21b')" type="button" value="Edit" class="small-edit-btn-2"></div>
                        @endcan

                    </div>
                    <div class="row  justify-content-center">
                        <div class="col-sm-6 text-right">
                            <div class="">
                                <input id="popup21" name="clockOut_time" type="number" class="form-control hide  clockOut_time custom-shadow border-1" placeholder="00" value="{{round($employeeSetting['clockOut_time']/60)}}">
                                <p style="color:red;" id="warning-message-clockOut_time" class="warning"></p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="cc"><button id="popup21a" onclick="updateEmpSetting('/employee_setting','{{$emp_id}}', 'clockOut_time')" class="btn btn-success hide mb-2">Save</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>