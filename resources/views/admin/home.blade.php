@extends('admin.layout.app')
@section('title', 'Home')
@push('scripts')
<style>
  
</style>
@endpush

@section('content')
<div class="wrapper wrapper-content animated fadeInRight" style="padding: 20px 10px 0px;">

    <div class="row">
        <div class="col-lg-3">
            <div class="widget style1 navy-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-files-o fa-5x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span> Courses</span>
                        <h2 class="font-bold">{{ $courses_count }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="widget style1 navy-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-graduation-cap fa-5x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span> Students </span>
                        <h2 class="font-bold">{{ $student_count }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="widget style1 lazur-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-vcard-o fa-5x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span> Instructor </span>
                        <h2 class="font-bold">{{ $instructor_count }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="widget style1 yellow-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-user-circle fa-5x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span> Admins </span>
                        <h2 class="font-bold">{{ $admin_count }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row  border-bottom white-bg dashboard-header" style="padding-top:12px;">
    <div class="col-md-3">
        <h4>Browsers & OS</h4>
        <!-- <small>You have 42 messages and 6 notifications.</small> -->
        <ul class="list-group clear-list m-t">
            @forelse($user_browsers as $broIndex => $user_browser)
                
                <li class="list-group-item fist-item">
                    <!-- <span class="pull-right">09:00 pm</span> -->
                    <span class="label label-primary">{{ $loop->iteration }}</span> 
                    {{ $user_browser->browser .', '.$user_browser->platform  }}
                </li>
                @empty
                    No browser detected
            @endforelse
            <!-- <li class="list-group-item fist-item">
                <span class="pull-right">09:00 pm</span>
                <span class="label label-primary">3</span> 
                Open new shop
            </li> -->
            
        </ul>
    </div>
    <div class="col-md-6">
        <div class="flot-chart dashboard-chart">
            <div class="flot-chart-content" id="flot-dashboard-chart"></div>
        </div>
        <div class="row text-left">
            <div class="col-xs-4">
            <div class=" m-l-md">
                <span class="h4 font-bold m-t block">{{ $total_page_views_count }}</span>
                <small class="text-muted m-b block">Overall Pages View
            </div>
            </div>
            <div class="col-xs-4">
            <span class="h4 font-bold m-t block">{{ $total_page_views_count }}</span>
            <small class="text-muted m-b block">Total Visits</small>
            </div>
            <div class="col-xs-4">
            <span class="h4 font-bold m-t block">{{ $total_view_count_today }}</span>
            <small class="text-muted m-b block">Today Visits</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="statistic-box">
            <!-- <h4>
            Project Beta progress
            </h4> -->
            <!-- <p>
            You have two project with not compleated task.
            </p> -->
            <div class="row text-center">
            <div class="col-lg-12">
                <canvas id="doughnutChart2" width="80" height="80" style="margin: 18px auto 0"></canvas>
                <h5 >Countries</h5>
            </div>
            <!-- <div class="col-lg-6">
                <canvas id="doughnutChart" width="80" height="80" style="margin: 18px auto 0"></canvas>
                <h5 >Maxtor</h5>
            </div> -->
            </div>
            <!-- <div class="m-t">
            <small>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</small>
            </div> -->
        </div>
    </div>
</div>


<div class="row" style="padding-top:12px;">
    <div class="col-lg-12">
        <div class="ibox float-e-margins" style="margin-bottom: 16px;">
            <div class="ibox-title">
                <h5>Latest Page Visits</h5>
                <div class="ibox-tools">
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                    
                    <a class="close-link">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="ibox-content">

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover dataTables-example" >
                <thead>
                <tr>
                    <th>URL</th>
                    <th>IP</th>
                    <th>Country & Region</th>
                    <th>Visits</th>
                    <th>User Events</th>
                    
                    <!-- <th style="width:130px;">Action</th> -->
                </tr>
                </thead>
                <tbody>
                @forelse($latest_page_views as $index => $latest_page_view)
                    @if(Str::contains($latest_page_view->url, '/user-behavior'))
                        @continue
                    @endif
                    <tr>
                        <td>
                            <a href="{{ $latest_page_view->url }}" target="_blank">
                                {{ $latest_page_view->url }}
                            </a>
                        </td>
                        <td>{{ $latest_page_view->ip_address }}</td>
                        <td>
                            <p style="margin:0;">{{ $latest_page_view->city .' '. $latest_page_view->country . ' ' . $latest_page_view->region .' '. $latest_page_view->postal}}</p>
                            
                        </td>
                        <td>{{ $latest_page_view->view_count }}</td>

                        <td>
                            @foreach($latest_page_view->userBehaviors as $key => $userBehavior)
                                

                                @if($userBehavior->event_type == 'scroll')
                                    @php
                                        // Decode the 'data' field from JSON to access the depth value
                                        $data = json_decode($userBehavior->data, true);
                                    @endphp

                                    @if(isset($data['depth']))
                                        <p style="margin:0;">Scroll Depth: {{ $data['depth'] }}%</p>
                                    @endif
                                @else
                                    <p style="margin:0;">
                                        {{ $userBehavior->event_type }}
                                    </p>

                                @endif
                            @endforeach

                        </td>
                    </tr>

                @empty
                
                <tr>
                    <td class="text-align:center;" colspan="4">No Record Found!</td>
                </tr>

                @endforelse
                
                
                </tbody>
                
                </table>
                {!! $latest_page_views->links() !!}
            </div>

            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
$(document).ready(function() {
    setTimeout(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            showMethod: 'slideDown',
            timeOut: 4000
        };
        
    }, 1300);
    toastr.success('Welcome, {{ addslashes(panel_profile_name() ?: 'User') }}');
});
</script>
<script>
        $(document).ready(function() {
            
            // Assuming you're passing 'view_counts' from the controller as JSON
    var viewCounts = @json($view_counts); // This will give you the array of objects with 'date' and 'total_views'

// Initialize arrays for chart data
var data1 = [];

// Populate data1 with view counts
viewCounts.forEach(function(item, index) {
    data1.push([index, item.total_views]);  // Push index as x and total_views as y
});

// Flot chart initialization
if ($("#flot-dashboard-chart").length) {
    $.plot($("#flot-dashboard-chart"), [
        {
            data: data1,  // The data to plot
            label: "Views in Last 7 Days"
        }
    ],
    {
        series: {
            lines: {
                show: false,
                fill: true
            },
            splines: {
                show: true,
                tension: 0.4,
                lineWidth: 1,
                fill: 0.4
            },
            points: {
                radius: 0,
                show: true
            },
            shadowSize: 2
        },
        grid: {
            hoverable: true,
            clickable: true,
            tickColor: "#d5d5d5",
            borderWidth: 1,
            color: '#d5d5d5'
        },
        colors: ["#1ab394", "#1C84C6"],
        xaxis: {
            ticks: viewCounts.map(function(item, index) { 
                return [index, item.date]; // Setting the ticks with date labels
            })
        },
        yaxis: {
            ticks: 4
        },
        tooltip: false
    });
}

            // var doughnutData = {
            //     labels: ["App","Software","Laptop" ],
            //     datasets: [{
            //         data: [300,50,100],
            //         backgroundColor: ["#a3e1d4","#dedede","#9CC3DA"]
            //     }]
            // } ;


            // var doughnutOptions = {
            //     responsive: false,
            //     legend: {
            //         display: false
            //     }
            // };


            // var ctx4 = document.getElementById("doughnutChart").getContext("2d");
            // new Chart(ctx4, {type: 'doughnut', data: doughnutData, options:doughnutOptions});

            var doughnutData = {
                labels: @json($countryLabels),
                datasets: [{
                    data: @json($countryData),
                    backgroundColor: @json($backgroundColors)
                }]
            };


            var doughnutOptions = {
                responsive: false,
                legend: {
                    display: false
                }
            };


            var ctx4 = document.getElementById("doughnutChart2").getContext("2d");
            new Chart(ctx4, {type: 'doughnut', data: doughnutData, options:doughnutOptions});

        });
      </script>
@endpush