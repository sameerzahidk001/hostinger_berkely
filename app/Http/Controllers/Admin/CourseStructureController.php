<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CourseStructureFirst;

class CourseStructureController extends Controller
{
    function index($id){

        $data['course'] = Course::withTrashed()->select('id', 'title', 'slug','course_structure_overview_first','course_exam_format_duration_overview','course_structure_section')
        ->with('courseStructuresFirst.subHeadingsFirst')
        ->where('id', $id)
        ->first();
        return view('admin.course.course-structure-first.index')->with($data);

    }

    function storeCourseStructureOverview(Request $request, $id){
        //return $request;
        $course = Course::withTrashed()->findOrFail($id);
        $course->course_structure_overview_first = $request->course_structure_overview_first;
        $course->course_exam_format_duration_overview = $request->course_exam_format_duration_overview;
        $course->course_structure_section = $request->course_structure_section;
        $courseUpdated = $course->save();

        $labelData = $request->input('label');
        //return $labelData['what_you_earn_img'];
        $filledFields = array_filter($labelData, function ($value) {
            return !is_null($value) && $value !== '';
        });

        if (!empty($filledFields)){
            //return 'filled';
            $createLabels = [
                'course_structure' => $labelData['course_structure'] ?? null,
            ];

            $dynamicLabel = $course->dynamicLabel()->first();
            if ($dynamicLabel) {
                $dynamicLabel->update($createLabels);
            } else {
                $assignLabels = $course->dynamicLabel()->create($createLabels);
            }
        }

        if($courseUpdated){
            //return 'if';
            session()->flash('success', 'Record updated successfully!');
        }else{
            //return 'else';
            session()->flash('warning', 'Failed to updated record!');
        }
        return redirect()->route('course.course-structure-first', ['id' => $id]);
    }

    function storeCourseStructurePartFirst(Request $request, $id){
        //return $request;
        $course = Course::withTrashed()->findOrFail($id);
        $courseStructureInserted = $course->courseStructuresFirst()->create([
            'title' => $request->course['title'] ?? NULL,
            'heading' => $request->course['heading'] ?? NULL,
            'exam_format' => $request->course['exam_format'] ?? NULL,
            'exam_duration' => $request->course['exam_duration'] ?? NULL,
            'overview' => $request->course['overview'] ?? null,
        ]);

        // Create the related Subheadings
        // foreach ($request->course['subheading'] as $subheadingData) {
        if (!is_null($request->course['subheading']['0']['subheading'] ) ) {
            foreach ($request->course['subheading'] as $index => $subheadingData) {

                $subheading = $courseStructureInserted->subHeadingsFirst()->create([
                    'sub_heading' => $subheadingData['subheading'],
                ]);
    
            }
        }

        if ($courseStructureInserted) {
            session()->flash('sucess', 'Record added successfullly!');
            
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }

        return redirect()->route('course.course-structure-first', ['id' => $id]);
    }

    function delCourseStructure($course_id, $id){
        
        $course = CourseStructureFirst::findOrFail($id);
        $recordDeleted = $course->delete();
        if ($recordDeleted) {
            session()->flash('sucess', 'Record deleted successfullly!');
            return redirect()->route('course.course-structure-first', ['id' => $course_id]);
            //return redirect()->back();
        } else {
            session()->flash('failed', 'Failed to delete Record!');
        }
    }

    function EditCourseStructurePart($id, $part_id){

        //return $id;
        $course_id = $id;
        $part_id = $part_id;
        $data['course'] = Course::withTrashed()->select('id', 'title', 'slug')
        ->with(['courseStructuresFirst' => function($query) use ($part_id) {
            $query->where('id', $part_id)->with('subHeadingsFirst');
        }])
        ->where('id', $course_id)
        ->first();
        
        return view('admin.course.course-structure-first.edit')->with($data);
    }

    function updateCourseStructurePart(Request $request){
        
        $course = Course::withTrashed()->findOrFail($request->course_id);
        $courseStructure = $course->courseStructuresFirst()->find($request->course['course_structures_id']);

        if ($courseStructure) {
            $courseStructureUpdated = $courseStructure->update([
                'title' => $request->course['title'] ?? null,
                'heading' => $request->course['heading'] ?? null,
                'exam_format' => $request->course['exam_format'] ?? null,
                'exam_duration' => $request->course['exam_duration'] ?? null,
                'exam_format' => $request->course['exam_format'] ?? null,
                'overview' => $request->course['overview'] ?? null,
            ]);

            // Update the related Subheadings
            $existingSubheadingIds = $courseStructure->subHeadingsFirst->pluck('id')->toArray();
            if (!empty($request->course['subheading'])) {
                $incomingSubheadingIds = array_column($request->course['subheading'], 'id');
                // Delete subheadings that are not in the request
                $subheadingsToDelete = array_diff($existingSubheadingIds, $incomingSubheadingIds);
                if (!empty($subheadingsToDelete)) {
                    $courseStructure->subHeadingsFirst()->whereIn('id', $subheadingsToDelete)->delete();
                }

                foreach ($request->course['subheading'] as $subheadingData) {
                    // Ensure sub_heading is not null
                    $subHeadingTitle = $subheadingData['subheading'] ?? 'Default Sub Heading Title';

                    $subheading = $courseStructure->subHeadingsFirst()->updateOrCreate(
                        ['id' => $subheadingData['id'] ?? null],
                        ['sub_heading' => $subHeadingTitle]
                    );

                }
            }else{
                $courseStructure->subHeadingsFirst()->delete();
                session()->flash('success', 'Record updated successfully!');
                return redirect()->route('course.course-structure-first', ['id' => $request->course_id]);
            }
            
            
            if($courseStructureUpdated){
                session()->flash('success', 'Record updated successfully!');
                return redirect()->route('course.course-structure-first', ['id' => $request->course_id]);
            }
        } 
        
    }
}
