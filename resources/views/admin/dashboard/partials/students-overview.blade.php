@php
    $reg = $studentOverviewStats['registration'] ?? [];
    $enr = $studentOverviewStats['enrollment'] ?? [];
@endphp
<div class="row" style="padding: 0 10px 12px;" id="students-overview-panel">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5 style="margin:0;font-weight:700;text-transform:uppercase;letter-spacing:.02em;">Students Overview</h5>
            </div>
            <div class="ibox-content">
                <style>
                    #students-overview-panel .student-overview-tabs > li > a {
                        color: #fff !important;
                        border: none !important;
                        border-radius: 3px 3px 0 0;
                        margin-right: 4px;
                        padding: 10px 18px;
                        font-weight: 700;
                        text-transform: uppercase;
                        background: #1c84c6;
                    }
                    #students-overview-panel .student-overview-tabs > li.active > a,
                    #students-overview-panel .student-overview-tabs > li > a:hover {
                        background: #155a8a;
                        color: #fff !important;
                    }
                    #students-overview-panel .student-stat-value {
                        font-size: 28px;
                        font-weight: 700;
                        line-height: 1.15;
                        margin: 0 0 6px;
                        color: #ed5565;
                    }
                    #students-overview-panel .student-stat-row {
                        display: flex;
                        flex-wrap: wrap;
                    }
                    #students-overview-panel .student-stat-row > [class*="col-"] {
                        display: flex;
                        float: none;
                        margin-bottom: 12px;
                        width: 20%;
                        max-width: 20%;
                        flex: 0 0 20%;
                    }
                    @media (max-width: 1199px) {
                        #students-overview-panel .student-stat-row > [class*="col-"] {
                            width: 33.333%;
                            max-width: 33.333%;
                            flex: 0 0 33.333%;
                        }
                    }
                    @media (max-width: 767px) {
                        #students-overview-panel .student-stat-row > [class*="col-"] {
                            width: 50%;
                            max-width: 50%;
                            flex: 0 0 50%;
                        }
                    }
                    #students-overview-panel .student-stat {
                        display: flex;
                        flex-direction: column;
                        justify-content: flex-start;
                        width: 100%;
                        min-height: 96px;
                        border: 1px solid #e7eaec;
                        border-radius: 4px;
                        padding: 12px 14px;
                        background: #f9f9f9;
                        color: #676a6c;
                    }
                    #students-overview-panel .student-stat-label {
                        font-size: 11px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: .02em;
                        line-height: 1.35;
                    }
                </style>

                <ul class="nav nav-tabs student-overview-tabs" role="tablist" style="margin-bottom:16px;border-bottom:none;">
                    <li class="active">
                        <a href="#student-overview-registration" data-toggle="tab">Overview</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="student-overview-registration">
                        <div class="row student-stat-row">
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($reg['total'] ?? 0) }}</div>
                                    <div class="student-stat-label">Total Registered</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($reg['today'] ?? 0) }}</div>
                                    <div class="student-stat-label">Registered Today</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($reg['yesterday'] ?? 0) }}</div>
                                    <div class="student-stat-label">Registered Yesterday</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($reg['this_week'] ?? 0) }}</div>
                                    <div class="student-stat-label">Registered This Week</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($reg['last_week'] ?? 0) }}</div>
                                    <div class="student-stat-label">Registered Last Week</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($reg['this_month'] ?? 0) }}</div>
                                    <div class="student-stat-label">Registered This Month</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($reg['last_month'] ?? 0) }}</div>
                                    <div class="student-stat-label">Registered Last Month</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($reg['this_year'] ?? 0) }}</div>
                                    <div class="student-stat-label">Registered This Year</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($enr['enrolled_total'] ?? 0) }}</div>
                                    <div class="student-stat-label">Enrolled Total Students</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($enr['enrolled_paid'] ?? 0) }}</div>
                                    <div class="student-stat-label">Invoice Fully Paid</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($enr['partially_paid_students'] ?? 0) }}</div>
                                    <div class="student-stat-label">Partially Paid Students</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="student-stat">
                                    <div class="student-stat-value">{{ (int) ($enr['invoice_unpaid_students'] ?? 0) }}</div>
                                    <div class="student-stat-label">Invoice Issued And Unpaid</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
