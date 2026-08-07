<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-rep-plugin">
                    <div class="table-responsive mb-0">
                        <table id="{{ $slug === 'admin' ? 'today-Datatable' : 'datatable' }}" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Leave Balance</th>
                                    <th>Last Month</th>
                                    <th>This Month</th>
                                    <th>This Week</th>
                                    <th>Today</th>
                                    <th>Clock Out</th>
                                    <th>Last Active</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($team as $employee)
                                @php
                                $emp_id = get_emp_id($employee->id);
                                @endphp
                                <tr>
                                    <td><span style="display:none;">{{ strtotime($employee->created_at) }}</span>{{ $employee->id }}
                                    </td>
                                    <td><a href="employee/attendance/team_report/{{encrypt(get_emp_id($employee->id))}}/{{Date('m')}}/{{Date('Y')}}/"> <i class="mdi mdi-link-variant"></i> {{ $employee->name }} </a></td>
                                    @php
                                    $leave= get_leave_balance($emp_id);
                                    $probation_comp= strtotime('+'. $employee->probation .'months',strtotime($employee->joining_date));
                                    $today= strtotime(date('d-m-Y'));
                                    @endphp
                                    @if($probation_comp>=$today)
                                    <td class="text-info">In probation</td>
                                    @else
                                    <td class="text-success">Remaning: {{$leave['remaning']}} </td>
                                    @endif
                                    <td>{{ get_workingHour_monthly($emp_id, Date('m', strtotime('-1 month'))) }}
                                    </td>
                                    <td>{{ get_workingHour_monthly($emp_id, Date('m')) }}</td>
                                    <td>{{ get_thisweek_hour($emp_id) }}</td>
                                    <td>{{ get_daily_hour($emp_id, date('Y-m-d')) }}</td>
                                    @php
                                    $str= employee_status($emp_id);
                                    $break=get_clockouts($emp_id, date('Y-m-d'));
                                    @endphp
                                    <td><span class="{{($break['break_remainig'] < 0 ? 'text-danger':'text-success' )}}">{{ 'Spent:' . round($break['break_spent'], 0). ' Min' }} <br> {{ ($break['break_remainig'] < 0 ? 'Exc:' : 'Rem:') . round($break['break_remainig'], 0) . ' Min'; }}</span></td>
                                    <td><span class="label-tag">Last Seen:</span><span class="last-seen">{{ last_seen_emploee($emp_id, date('Y-m-d')) }}</span><br> <span class="label-tag">Today</span> {!!$str!!}</td>
                                    @if ($employee->tracking == 1)
                                    <td class="text-center">
                                        @can('permission', 'edit')
                                        <a class="text-success update_tracking" data-state="1" data-id="{{ $emp_id }}"><i class=" fa fa-toggle-on"></i></a></a>
                                        @endcan
                                    </td>
                                    @elseif ($employee->tracking == 0)
                                    <td class="text-center">
                                        @can('permission', 'edit')
                                        <a class="text-primary update_tracking" data-state="0" data-id="{{ $emp_id }}"><i class=" fa fa-toggle-off"></i></a>
                                        @endcan
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end col -->
</div>