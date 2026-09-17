@extends('admin.layout.app')
@section('title', 'Add Instructor')
@push('style')
    <style>
        .mb {
            margin-bottom: 1.5rem;
        }

        .card {
            border-radius: 8px;
            overflow: hidden;
        }

        .card img {
            border-bottom: 1px solid #ddd;
        }

        .course-instructors-grid > [class*="col-"] {
            margin-bottom: 1.5rem;
        }
    </style>

@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Instructor</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                </li>
                <li>
                    <a>Instructor</a>
                </li>
                <li class="active">
                    <strong>Add New Instructor</strong>
                </li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Add New Instructors</h5>
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
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <form role="form" action="{{ route('admin.courses.instructors.update', ['id' => $course->id]) }}" method="POST">
                            @csrf
                            <input type="hidden" value="{{ $course->id }}" name="course_id">
                            <div class="row">
                                <div class="col-md-6 mb">
                                    <label for="head_of_faculty_id">Head of the Faculty</label>
                                    <select name="head_of_faculty_id" id="head_of_faculty_id" class="form-control">
                                        <option value="">—</option>
                                        @foreach ($instructors as $instructor)
                                            <option value="{{ $instructor->id }}" @selected(old('head_of_faculty_id', $headOfFacultyId ?? null) == $instructor->id)>
                                                {{ $instructor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb">
                                    <label for="instructor_ids">Instructors</label>
                                    <select name="instructor_ids[]" id="instructor_ids" class="form-control" multiple size="8">
                                        @foreach ($instructors as $instructor)
                                            <option value="{{ $instructor->id }}" @selected(collect(old('instructor_ids', $selectedInstructorIds ?? []))->contains($instructor->id))>
                                                {{ $instructor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="help-block">Hold Ctrl/Cmd to select multiple instructors. They show on the course page, 2 per row on large screens.</span>
                                </div>
                                <div class="col-lg-12">
                                    <button type="submit" class="btn btn-primary" style="float: right;margin-top: 15px;">Save Faculty</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Course Instructors List</h5>
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
                        <div class="row mt-4 course-instructors-grid">
                            @forelse ($assignIntructors as $assignIntructor)
                                <div class="col-md-6">
                                    <div class="card" style="border: 1px solid #f0f0f0; border-radius: 8px; overflow: hidden; position: relative;">
                                        <form id="delete-form-{{ $assignIntructor->id }}" action="{{ route('admin.courses.instructors.delete', ['id' => $course->id]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $assignIntructor->id }}">
                                            <button type="button" class="btn btn-sm btn-light" onclick="confirmDelete({{ $assignIntructor->id }})"
                                                    style="position: absolute; top: 8px; right: 8px; z-index: 1;">
                                                &times;
                                            </button>
                                        </form>

                                        <img src="{{ asset($assignIntructor->image) }}"
                                            class="card-img-top"
                                            alt="{{ $assignIntructor->name }}"
                                            style="height: 180px; width: 100%; object-fit: cover;">

                                        <div class="card-body text-center">
                                            <h5 class="card-title font-weight-bold mb-2">
                                                {{ $assignIntructor->name }}
                                                @if($loop->first)
                                                    <small class="text-muted">(Head of Faculty)</small>
                                                @endif
                                            </h5>
                                            <p class="text-muted mb-2" style="font-size: 14px;">
                                                {{ $assignIntructor->experience ?? 'No experience info' }}
                                            </p>

                                            @php
                                                $educationList = explode(',', $assignIntructor->education ?? '');
                                            @endphp

                                            @if (!empty($educationList[0]))
                                                <ul class="list-unstyled mb-0" style="font-size: 13px;">
                                                    @foreach ($educationList as $edu)
                                                        <li>🎓 {{ trim($edu) }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-muted">No education info</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-md-12">
                                    <p class="text-muted text-center">No instructors assigned yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
@endpush
