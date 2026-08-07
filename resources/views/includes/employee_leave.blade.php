@php
$emp_id= get_emp_id(auth()->user()->id);
$leaveinfo=get_leave_balance($emp_id);
@endphp
<div class="row">
    <div class="col-xl-3 col-md-3">
        <div class="card mini-stat bg-secondary text-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="float-left mini-stat-img mr-4">
                        <span class="ti-server" style="font-size: 20px"></span>
                    </div>
                    <h5 class="font-16 text-uppercase mt-0 text-white-50">Leave <br>Balance </h5>
                    <h4 class="font-500">{{$leaveinfo['remaning']}}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card mini-stat bg-success text-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="float-left mini-stat-img mr-4">
                        <span class="ti-thumb-up" style="font-size: 20px"></span>
                    </div>
                    <h5 class="font-16 text-uppercase mt-0 text-white-50">Leave <br>Approved </h5>
                    <h4 class="font-500">{{$leaveinfo['approved']}}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card mini-stat bg-danger text-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="float-left mini-stat-img mr-4">
                        <span class="ti-thumb-down" style="font-size: 20px"></span>
                    </div>
                    <h5 class="font-16 text-uppercase mt-0 text-white-50"> Leave<br>Cancelled </h5>
                    <h4 class="font-500">{{$leaveinfo['canceled']}}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-3">
        <div class="card mini-stat bg-info text-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="float-left mini-stat-img mr-4">
                        <span class="ti-write" style="font-size: 20px"></span>
                    </div>
                    <h5 class="font-16 text-uppercase mt-0 text-white-50"> Half<br>Leave </h5>
                    <h5 class="font-500">{{$leaveinfo['half']}}<span style="font-size: small; color:#420b0b; font-weight: 400;"> (2 Half leaves = 1 Full)</span></h5>
                </div>
            </div>
        </div>
    </div>
</div>