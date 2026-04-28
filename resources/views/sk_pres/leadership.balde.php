@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- PAGE HEADER -->
        <div class="col-12 mb-3">
            <h4 class="fw-bold">SK Barangay Leadership Profiles</h4>
            <p class="text-muted">Leadership directory for all SK Councils across Lipa City</p>
        </div>

        <!-- TABS -->
        <div class="col-12 mb-4">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link {{ request('tab') != 'transition' ? 'active' : '' }}"
                       href="{{ route('leadership') }}">
                        Leadership Directory
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('tab') == 'transition' ? 'active' : '' }}"
                       href="{{ route('leadership', ['tab' => 'transition']) }}">
                        Leadership Transition Guide
                    </a>
                </li>
            </ul>
        </div>

        <!-- ================= LEFT: DIRECTORY ================= -->
        @if(request('tab') != 'transition')
        <div class="col-md-12">

            <!-- Federation President -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-danger text-white">
                    SK Federation President
                </div>
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center me-3"
                         style="width:60px;height:60px;">
                        {{ strtoupper(substr($president->first_name,0,1)) }}
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $president->first_name }} {{ $president->last_name }}</h6>
                        <small class="text-muted">{{ $president->email }}</small><br>
                        <small>{{ $president->phone_number }}</small>
                    </div>
                </div>
            </div>

            <!-- Barangay Councils -->
            <div class="card shadow-sm">
                <div class="card-header">
                    Barangay SK Councils
                </div>
                <div class="card-body">

                    @foreach($barangays as $barangay)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $barangay->barangay_name }}</strong>
                                <span class="badge bg-secondary">
                                    {{ $barangay->councils->count() }} officers
                                </span>
                            </div>

                            <!-- Officers -->
                            <div class="mt-2">
                                @foreach($barangay->councils as $council)
                                    <small class="d-block">
                                        • {{ $council->name }} ({{ $council->position }})
                                    </small>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

        </div>
        @endif

        <!-- ================= RIGHT: TRANSITION GUIDE ================= -->
        @if(request('tab') == 'transition')
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    Leadership Transition & Account Handover Process
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <h6>Overview</h6>
                        <p class="text-muted">
                            This process ensures continuity of governance and proper account handover.
                        </p>
                    </div>

                    <ol class="list-group list-group-numbered">

                        <li class="list-group-item">
                            <strong>Election Results Verification</strong><br>
                            Confirm newly elected officials.
                        </li>

                        <li class="list-group-item">
                            <strong>Deactivation of Old Accounts</strong><br>
                            Disable access of outgoing officials.
                        </li>

                        <li class="list-group-item">
                            <strong>Creation of New Accounts</strong><br>
                            Register new SK officials in the system.
                        </li>

                        <li class="list-group-item">
                            <strong>Account Credentials Distribution</strong><br>
                            Provide login credentials securely.
                        </li>

                        <li class="list-group-item">
                            <strong>Orientation & Training</strong><br>
                            Introduce platform usage and responsibilities.
                        </li>

                    </ol>

                    <div class="alert alert-info mt-4">
                        <strong>Important:</strong>
                        Ensure all credentials are updated and secure.
                    </div>

                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection