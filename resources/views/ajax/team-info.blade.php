<div class="team-info-modal">
<div class="card-body">
    <h4 class="card-title">{{$team_name}}</h4>
    <hr>
    <!-- Tab panes -->
    <div class="tab-content">
        <div class="tab-pane active p-3" id="home1" role="tabpanel">
            <form method="POST" action="{{ route('update.team') }}">
                @csrf
                <input type="hidden" name='team_id' value="{{ $team->id }}">
                <table>
                    <thead>

                    </thead>
                    <tbody>
                    </tbody>

                    <div class="row">
                        <div style="margin: 20px; background:#1c4e80; color:#ffffff" class="col-md-12">
                            <h4>Team Members</h4>
                        </div>
                        @foreach ($team->users as $user)
                        @php
                        $emp_id = get_emp_id($user->id);
                        $emp = employee_details($emp_id);
                        if($emp->status == 1){
                        @endphp
                        @php

                        $profile_pic_url = URL::asset('assets/images/profile1.png');

                        $position='';

                        @endphp
                        @if($emp)
                        @php
                        if($emp->profile_pic!=''){
                        $profile_pic_url = URL::asset('storage/assets/profile_pics/' . $emp->profile_pic);
                        }
                        $position = $emp->position;
                        @endphp
                        @endif
                        <div class="col-md-4">
                            <div class="user-details card">
                                <div class="card-content">
                                    <img src="{{ URL::asset($profile_pic_url) }}" class="mx-2 rounded-circle text-center" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;" alt="">
                                    <span class="full-name">{{ $user->name }}
                                        {{ $user->pivot->is_manager ? '(Manager)' : '(Member)' }}</span>
                                </div>
                            </div>
                        </div>

                        @php
                        }
                        @endphp
                        @endforeach

                    </div>
                    <div class="row">
                        <div style="margin: 20px; background:#1c4e80; color:#ffffff" class="col-md-12">
                            <h4>Other Users</h4>
                        </div>
                        @foreach ($users as $user)
                        @unless ($team->users->contains('id', $user->id))
                        @php
                        $emp_id = get_emp_id($user->id);
                        $emp = employee_details($emp_id);
                        if($emp->status == 1){
                        @endphp

                        @php
                        $profile_pic_url = URL::asset('assets/images/profile1.png');
                        $position='';

                        @endphp
                        @if($emp)
                        @php
                        if($emp->profile_pic!=''){
                        $profile_pic_url = URL::asset('storage/assets/profile_pics/' . $emp->profile_pic);
                        }
                        $position = $emp->position;
                        @endphp
                        @endif
                        <div class="card col-md-4">
                            <div class="user-details">

                                <input id="check_box" name="users[]" type="checkbox" value="{{ $user->id }}" class="checkbox">
                                <img src="{{ URL::asset($profile_pic_url) }}" class="mx-2 rounded-circle text-center" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;" alt="">
                                <span class="full-name">{{ $user->name }}</span>
                                <span class="position">({{$position}})</span>
                            </div>
                        </div>
                        @php
                        }
                        @endphp
                        @endunless
                        @endforeach

                    </div>
                </table>


                <div class="form-group">
                    <div class="float-right">
                        <button type="submit" class="btn btn-primary waves-effect waves-light">
                            Update
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>