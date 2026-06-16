@extends('layouts.app')
@push('style')

@endpush

@section('content')

<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Complaints and Misconducts</span>
            
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Complaints and Misconducts

            </h2>
        </div>
        <p clas="text-[18px] text-white">
            Find out what it's like to take a course from current and past learners.
        </p>
        <div class="flex gap-6 mt-4 md:mt-auto">
            <a href="{{ route('contact') }}"
                class="text-center border py-4 px-3 w-full border-primary_orange bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">
                Enquire
            </a>
            <a href="{{ route('school-calender') }}"
                class="text-center border py-4 px-3 w-full border-primary_orange hover:bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">
                Join our next event
            </a>
        </div>

    </div>
    <div class=" flex xl:px-0 md:top-0 md:absolute md:right-0 z-40 md:w-[50%] m-0 flex-1 flex-col gap-4 md:h-full">
        <img src="{{ asset('frontend/images/jpg/60.jpg') }}"
            alt="" class="object-cover h-full md:max-h-full md:w-full md:h-full">
    </div>
</section>

<section class="bg-[#efefef] w-full flex flex-col gap-5 px-3 md:px-16 lg:px-[71px] md:flex-row">


    @include('components.website-privacy-sidebar')

    <main class="md:w-2/3 lg:w-3/4 w-full py-4 min-h-screen">
        
        <div class="px-4 py-2 bg-white">
        
            <!-- <h2 class="text-2xl font-bold mb-2">PERSONAL INFORMATION WE COLLECT:</h2>
            <p class="mb-4">Our commitment lies in fostering a profound reverence for the legal system among our students. Embracing and enforcing the values of equity, impartiality, and responsibility are at the core of our educational beliefs and crucial for establishing a secure and well-managed school setting.</p> -->

            <h1><strong>COMPLAINTS POLICY AND PROCEDURE</strong></h1>
            <h2 class="text-xl  mb-2"><strong>1. INTRODUCTION</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">
                1.1 BERKELEY is committed to providing a high quality service for all its students, clients and the community. Its policy is to welcome and to try to satisfy complaints and observations from all who use the BERKELEY and to use the
                information to improve the services it offers.
            </p>
            <p class="mb-2">
                1.2 The BERKELEY will deal with legitimate complaints in a fair, prompt and objective manner. Complaints will be handled without recrimination and students and members of the public will not be disadvantaged by raising a complaint.
                Anonymous complaints will not be accepted. All information will be kept in strict confidence and shared only on a need- to-know basis. BERKELEY staff are expected to respond positively to complaints and to alert students or members of
                the public to the Complaints Procedure.
            </p>
            <p class="mb-2">1.3 The BERKELEY will be fair in its treatment of all those who complain irrespective of all protected characteristics.</p>
            <p class="mb-2">1.4 Students who are enrolled with a partner organisation but who study at BERKELEY are able to follow the complaints procedure of that organization.</p>
            <p class="mb-2">
                1.5 Complaints will be dealt with promptly and constructively. The outcomes of any complaint will be shared with the complainant and any BERKELEY staff involved. Complaints made which, on investigation, turn out to be malicious, may
                result in disciplinary or other further action.
            </p>
            <p class="mb-2">
                1.6 If the complaint is about a course or a service offered by the BERKELEY, the manager of the area concerned will be the complaints officer deliberating on the complaint unless the complaint is about that manager. The Center Manager
                Students and Curriculum will deal with all appeals against the outcomes of complaints unless that complaint involves the Center Manager Students and Curriculum in which case the appeal will be heard by another senior manager.
            </p>
            <p class="mb-2">
                1.7 The Center Manager Students and Curriculum has overall responsibility for the Complaints Procedure and may nominate another person to investigate a complaint if there is a conflict of interest between being the manager of an area
                and being the complaints officer investigating the complaint as outlined in 1.5 above.
            </p>
            <p class="mb-2">1.8 If the complaint is against the Principal or a member, then the Complaints Officer will be either the Principal if not the subject of the complaint.</p>
            <h2 class="text-xl  mb-2"><strong>2. SCOPE OF COMPLAINTS PROCEDURE</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">2.1 The Procedure deals with complaints arising from:</p>
            <p class="mb-2">• The quality of the provision of academic services or training including teaching, course content, tutoring and student support</p>
            <p class="mb-2">• Incorrect or misleading information about services provided by the BERKELEY</p>
            <p class="mb-2">• The quality of support services provided by the BERKELEY including administration of fees, enrolment and examination registration</p>
            <p class="mb-2">• Unacceptable behavior by BERKELEY staff</p>
            <p class="mb-2">2.2 Separate procedures exist for:</p>
            <p class="mb-2">• Public interest disclosure (Whistleblowing)</p>
            <p class="mb-2">• Student discipline</p>
            <p class="mb-2">• Academic assessment appeals</p>
            <p class="mb-2">2.3 Any incidents that may be related to radicalisation must be reported to the Safeguarding Team and the Principal or Center Manager Students and Curriculum even if, subsequently, it is identified as a false alarm.</p>
            <h2 class="text-xl  mb-2"><strong>3. HOW TO COMPLAIN</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">
                3.1 The Student Charter in the student handbook prompts guidance from Course Leaders as to how comments and complaints may be made and how to obtain impartial advice. All students will receive a copy of the Charter at Induction and are
                able to access.
            </p>
            <p class="mb-2">3.2 The Staff Guidelines refers to policies and procedures and new staff are informed of this Complaints Procedure during their induction programme.</p>
            <p class="mb-2">3.3 The Complaints Procedure and Complaint forms are available from Reception, Information Services, and IT Centres. It is available in other forms on request from Information Services.</p>
            <p class="mb-2">The Complaints Procedure and Complaint form is accessible through the external web site.</p>
            <p class="mb-2">
                3.4 If a complainant writes directly to the Principal or the Center Manager Students and Curriculum, (either directly or via their PA) the complaint will be forwarded to the Complaints Officer, as per section 5 of this document. The
                Principal and Center Manager Students and Curriculum cannot be involved at this stage with the investigation of the complaint. An acknowledgement will be sent to the complainant.
            </p>
            <p class="mb-2">Very serious complaints, for example those that involve the Police, will be responded to in writing by the Student Services Manager, Center Manager Students and Curriculum or the Principal as appropriate.</p>
            <h2 class="text-xl  mb-2"><strong>4. SUPPORT FOR THOSE INVOLVED IN A COMPLAINT</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">Support, if required, is available for all those involved in a complaint:</p>
            <p class="mb-2">• Representation: parent, guardian, friend, employer, supporter, Student Services.</p>
            <p class="mb-2">• Help with completing the Complaint Form: Student Services Manager</p>
            <p class="mb-2">• Guidance in understanding the procedure: Student Services Manager</p>
            <p class="mb-2">• Support during the procedure: BERKELEY Wellbeing Officer, Student Services Team.</p>
            <p class="mb-2">If any further help is needed, Information Services should be contacted.</p>
            <h2 class="text-xl  mb-2"><strong>5. COMPLAINT PROCEDURE</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">Stage 1: Informal resolution of complaints</p>
            <p class="mb-2">
                5.1 Most complaints should be able to be resolved immediately with discussion between the complainant and the appropriate member of staff. The complaint must be made orally or in writing and the manager receiving the complaint should
                make a response within 10 working days, orally or in writing. It is expected that staff are tactful and courteous in dealing with a complaint. If the complainant is dissatisfied with the response received, they should then be guided to
                using the formal procedure.
            </p>
            <p class="mb-2">
                5.2 If a complaint is raised with the expectation of a refund or partial refund, the student or member of the public must make that clear at the time of raising the complaint. The student or member of the public will then be notified of
                the BERKELEY’s Refunds Policy.
            </p>
            <p class="mb-2">Stage 2: Formal procedure</p>
            <p class="mb-2">
                5.3 If Stage 1 is unable to resolve the complaint informally, a formal complaint should be made in writing within 5 working days of the feedback.. In exceptional circumstances, a longer period will be considered. The formal complaint
                should be sent to the Principal and the Center Manager Students and Curriculum as in 3.4 above and will be logged. The formal complaint will be acknowledged in writing to the complainant within 5 working days.
            </p>
            <p class="mb-2">
                5.4 The Complaints Officer or delegated independent investigator will acknowledge the complaint and carry out an assessment of the complaint within 5 working days. More serious or unusual complaints that may represent a conflict of
                interest, will be investigated by an alternative Complaints Officer appointed by the BERKELEY and the complainant will be notified of any additional time required in this situation.
            </p>
            <p class="mb-2">
                5.5 The Complaints Officer will carry out an investigation of the complaint and may interview the complainant; the respondent; witnesses to the matter or events and anyone they believe may have a role in establishing or disproving the
                complaint, as necessary. They will prepare a summary report within 10 working days of the assessment.
            </p>
            <p class="mb-2">
                5.6 The Complaints Officer will record the outcome of the complaint in the Register of Complaints and either arrange a meeting to deliver the outcome or notify all those involved in writing as appropriate. All outcomes will be confirmed
                in writing to all those involved.
            </p>
            <p class="mb-2">
                5.7 If the complaint involves a student, they will be offered the support of a relevant member of staff as set out in 4 above. All students will be encouraged to bring a supporter to the interview. Vulnerable Adults and those under 16
                years of age must have the support of their care worker, or a person of their choice, who can act as their advocate and the Principal must be informed.
            </p>
            <p class="mb-2">5.8 If the complaint involves the Principal, the investigator (who will be the Chair of the Audit Committee or equivalent, (see section 1.7) will report the outcome of their investigation to the Chair of the Corporation.</p>
            <p class="mb-2">
                5.9 A formal complaint should be resolved within 20 working days of the receipt of the original formal complaint. If it appears that a decision will not be reached within the due period, those involved will be advised of the need for a
                longer period.
            </p>
            <p class="mb-2">The Principal of the BERKELEY has the right to assign different post holders to those stated above to ensure that the complainant gets a fair consideration of their complaint.</p>
            <h2 class="text-xl  mb-2"><strong>6. APPEAL PROCEDURE</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">Stage 3: The Appeals Procedure</p>
            <p class="mb-2">
                6.1 Either the complainant or the respondent may appeal in writing, stating detailed reasons for their appeal to the Center Manager Students and Curriculum within 5 working days of receiving written confirmation of the outcome of the
                formal complaint. The appeal will be acknowledged within 5 working days and the Center Manager Students and Curriculum or designated senior manager will review and notify the final decision in writing to all those involved within 20
                working days of receiving the appeal.
            </p>
            <p class="mb-2">Stage 4: Conduct of Appeal</p>
            <p class="mb-2">
                6.2 If the complainant is not happy with the conduct of the appeal, the complaint can be taken further to the Principal of the BERKELEY, who will investigate the conduct of the investigation. This would be to review the performance of
                the procedure and not the appeal decision.
            </p>
            <p class="mb-2">6.3 If the BERKELEY does not resolve the appeal to the complainant’s satisfaction, then they should contact:</p>
            <p class="mb-2">Education and Skills Funding Agency or Complaints Team: 2601, 26th floor, Sheikh Rashid Tower, Dubai World Trade Centre, Dubai, UAE.</p>
            <p class="mb-2">
                Students will be issued with a Completion of Procedures (COP) letter that will inform them of their entitlement to complain to the Office of the Independent Adjudicator and will inform them of the deadline 12 months from the date of the
                decision by which any complaint must be submitted.
            </p>
            <h2 class="text-xl  mb-2"><strong>7. Monitoring the Procedure</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">7.1 It is essential that complaints are resolved promptly. The Quality Manager will check the Register of Complaints every two weeks to identify outstanding complaints and ensure that the procedure follows the set time limits.</p>
            <p class="mb-2">
                7.2 The Quality Manager will present a schedule of complaints received and outcomes to the Senior Management Team on a termly basis. The Center Manager Students and Curriculum or and Quality Manager will produce an annual report for the
                Corporation, based on the Register of Complaints analysis, covering the following items:
            </p>
            <p class="mb-2">• Number of complaints of each type</p>
            <p class="mb-2">• Time taken to process complaints</p>
            <p class="mb-2">• List of outstanding complaints</p>
            <p class="mb-2">• Outcomes to complaints</p>
            <p class="mb-2">• Appeals made and results of appeals</p>
            <p class="mb-2">• Analysis of complaints and outcomes by certain protected characteristics.</p>
            <p class="mb-2">7.3 The Center Manager Students and Curriculum will hold a record of all complaints for 5 years for audit purposes.</p>
            <p class="mb-2"></p>
            <p class="mb-2"></p>
            <hr />
            <h1><strong>ACADEMIC MISCONDUCT AND MALPRACTICE POLICY</strong></h1>
            <p class="mb-2"></p>
            <h2 class="text-xl  mb-2"><strong>1. INTRODUCTION</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">
                Plagiarism, cheating, collusion and attempting to obtain an unfair academic advantage are forms of academic misconduct and are entirely unacceptable for any student at a further education BERKELEY. This policy defines what the
                BERKELEY means by plagiarism, give examples of the categories of other forms of unacceptable academic misconduct outside examinations, gives guidance to staff to help prevent the occurrence of such misconduct, determines the
                procedures to be adopted in suspected cases and indicates the academic penalties which may be appropriate in proven cases.
            </p>
            <p class="mb-2">
                The aim of this policy is to promote honest practice that encourages original work. It is intended to maintain the integrity of the BERKELEY’s academic awards and procedures and to give any students or staff affected a fair
                opportunity to respond to any allegation of academic misconduct. Each case will be determined on its own facts and merits. It may be necessary to adjust the procedures to allow a proper investigation or to ensure fairness to those
                concerned in any particular case. It may be necessary for the BERKELEY to seek legal advice in specific cases. The procedures in this policy are not contractual in nature and there is no right to compensation for any amendment to the
                procedures.
            </p>
            <p class="mb-2">Students enrolled by a partner organisation where the course is delivered at BERKELEY should follow the academic malpractice and misconduct guidelines published by that organization.</p>
            <p class="mb-2">
                Staff and students must read and understand the policy and its implications, and sign to this effect. The policy will be reproduced in induction literature, the student handbook or similar publication, so that all students are aware of
                its existence.
            </p>
            <h2 class="text-xl  mb-2"><strong>2. DEFINITIONS – WHAT ACTIVITIES ARE INCLUDED IN THE ACADEMIC MISCONDUCT AND MALPRACTICE POLICY</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">
                Plagiarism is the presentation of someone else’s work, words, images, ideas, opinions or discoveries, whether published or not, as one’s own, or alternatively appropriating the artwork, images or computer generated work of others,
                without properly acknowledging the source, with or without their permission.
            </p>
            <p class="mb-2">Plagiarism by students can occur in examinations but is most likely to occur outside sat or unseen exams, i.e. in coursework, assignments, portfolios, essays and dissertations.</p>
            <p class="mb-2">Examples of plagiarism in such a context would include:</p>
            <p class="mb-2">a) Directly copying from written physical, pictorial or written material, without crediting the source;</p>
            <p class="mb-2">b) Paraphrasing someone else’s work, without crediting the source;</p>
            <p class="mb-2">Examples of other forms of academic misconduct (such as cheating, collusion and attempting to obtain an unfair academic advantage) would include:</p>
            <p class="mb-2">a) Getting someone else to produce part or all of the work submitted;</p>
            <p class="mb-2">b) Colluding with one or more student(s) to produce a piece of work and submitting it individually as one’s own;</p>
            <p class="mb-2">c) Copying the work of another student, with or without their permission;</p>
            <p class="mb-2">d) Knowingly allowing another student to copy one’s own work;</p>
            <p class="mb-2">e) Resubmitting one’s own previously graded work;</p>
            <p class="mb-2">f) Using forbidden notes or books in producing assigned work or tests;</p>
            <p class="mb-2">g) Fabrication of results (including experiments, research, interviews, observations).</p>
            <p class="mb-2">
                The use of the word ‘academic’ in the title seeks to define the scope of policy as it relates to the delivery and assessment of the curriculum. It is intended to include vocational courses and assessed programmes including all
                vocationally relevant qualifications, diplomas, certifications and professional qualifications.
            </p>
            <h2 class="text-xl  mb-2"><strong>3. BERKELEY ACADEMIC MISCONDUCT POLICY – ACTION TO BE TAKEN BY STAFF</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">Plagiarism, cheating and collusion and attempting to obtain an unfair academic advantage are entirely unacceptable and not allowed. These forms of academic misconduct will be subject to disciplinary regulations.</p>
            <p class="mb-2">To prevent the occurrence of academic misconduct, staff should:</p>
            <p class="mb-2">a) Inform students clearly of the policy on academic misconduct (Malpractice) and of the guidelines, recording the date/s and occasion/s for future reference.</p>
            <p class="mb-2">b) Include statements on academic misconduct and links to the policy in the student handbook and course handbooks, as well as referencing this policy in other relevant policies to ensure consistency throughout the BERKELEY.</p>
            <p class="mb-2">c) Make students aware of the disciplinary penalties for academic misconduct at the earliest stage of the course.</p>
            <p class="mb-2">d) Provide students with guidance on the format of formal acknowledgement of source material see for guidance on this.</p>
            <p class="mb-2">e) Inform students that they are not permitted to submit work within text boxes as plagiarism checkers may not be able to read this information and some awarding bodies will not accept work in this manner.</p>
            <p class="mb-2">f) Use the plagiarism tool provided online submission of work.</p>
            <p class="mb-2">g) Inform students, in writing if possible, of the extent to which they can collaborate in coursework. Please refer to the notes in the guidelines from the awarding body as to what is, and is not, allowed when collaborating.</p>
            <p class="mb-2">h) Be aware that most students are very computer literate and can scan text and surf the web for model essays, etc., with ease.</p>
            <p class="mb-2">i) Use procedures for assessing work to make plagiarism, cheating and collusion more detectable. This might include:</p>
            <p class="mb-2">• ensuring that coursework assessment is supported by unseen and supervised work under test conditions,</p>
            <p class="mb-2">• annually reviewing and updating assignment topics and changing them where permitted by the awarding body on at least a 5-yearly cycle.</p>
            <p class="mb-2">• tailor generic assignments where permitted to reflect the progression interests and opportunities that students are likely to encounter.</p>
            <p class="mb-2">• get to know the style of student’s writing/submissions, early on in the course; compare subsequent work to initial assessment tests.</p>
            <p class="mb-2">• Mark/assess a class group’s coursework on a single occasion, to enhance the likelihood of the assessor spotting plagiarised passages.</p>
            <p class="mb-2">• Search phrases from text that is suspected of being plagiarised using ‘Google’ or an equivalent search engine.</p>
            <h2 class="text-xl  mb-2"><strong>4. BERKELEY ACADEMIC MISCONDUCT POLICY – FOR STUDENTS</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">The following are dishonest and therefore unacceptable and not allowed by the BERKELEY:</p>
            <p class="mb-2">• Taking someone else’s work, images or ideas and passing it off as your own (This is called plagiarism),</p>
            <p class="mb-2">• Accessing the work of others stored as digital information and passing it off as your own</p>
            <p class="mb-2">• Cheating, that is, acting unfairly or dishonestly to gain an advantage</p>
            <p class="mb-2">• Secretly agreeing with others to cheat or deceive. (This is known as collusion)</p>
            <p class="mb-2">• Collaborating with other students to pass off collectively produced work as one’s own, beyond or outside any request by teaching staff for groups of students to collaborate on projects or assignments. This is known as syndication.</p>
            <p class="mb-2">
                All these are called academic misconduct or malpractice. If you are discovered or suspected of doing any of the things shown in the list above, the BERKELEY will investigate and may take disciplinary action against you. (That is, you
                will be subject to the BERKELEY Code of Conduct procedures.)
            </p>
            <p class="mb-2">This is what is expected of you whilst you are at the BERKELEY-</p>
            <p class="mb-2">a) You will only hand in your own original work for assessment and will sign digitally on the VLE or by hand on an awarding body or assignment form to confirm the work as your own.</p>
            <p class="mb-2">
                b) When you have used information provided by someone else you will acknowledge this by giving the person’s name and where you found the information in your work (or in your portfolio) as you go along. For example, if you use someone
                else’s words you will enclose the quote with inverted commas. You will also repeat this information at the end of the piece (this is called a bibliography/references section). The same applies if you have received help. This is the
                standard practice in the world of learning. Your tutor or lecturer will give you help with this. You should seek advice and guidance from tutors if you are unsure how to do this properly.
            </p>
            <p class="mb-2">c) You will show when you have downloaded information from the internet and will cite a full web address in the reference.</p>
            <p class="mb-2">d) When submitting work via the VLE you are able to submit as a draft and are able to read the plagiarism checking report so that you can amend any incorrect referencing prior to the final submission.</p>
            <p class="mb-2">
                e) You will never use another’s digital storage as if it is your own work, nor copy work from digital storage belonging to someone else and use it as if it were your own. You will never use someone else’s artwork, pictures or graphics
                (including graphs, spreadsheets etc.) as if they were made by you
            </p>
            <p class="mb-2">f) You will never let other students use or copy from your work and pass it off as if they had done it themselves</p>
            <p class="mb-2">
                g) You can expect all cases of suspected academic misconduct and malpractice to be fully investigated using the BERKELEY Code of Conduct procedures and making reference to the guidelines. It is expected that the allegation will be
                reported to the awarding body. If proved, you can expect the BERKELEY to take action against you. What happens will depend on how serious what you have done appears to the BERKELEY.
            </p>
            <p class="mb-2">
                h) The member of staff who has looked into what you have done will decide how serious the case appears at first. This person will consult with senior colleagues when a moderate or serious case is suspected. The claims that you have done
                something illegal or wrong (the allegations) will be written down so that you know the case you have to answer.
            </p>
            <p class="mb-2">The actions taken by the BERKELEY, if they believe from the evidence you have done something wrong, may include the following:</p>
            <p class="mb-2">When what you have done is thought to be a minor case of academic misconduct –</p>
            <p class="mb-2">a) What you have done will be discussed with you in a private tutorial with your Course Leader or Academic Tutor.</p>
            <p class="mb-2">b) You will be given a warning about how you must act in the future</p>
            <p class="mb-2">c) You may have marks from your piece of work taken away or you may have work returned to re-do and hand in for remarking</p>
            <p class="mb-2">d) If this has happened before, you will go straight to a second stage interview</p>
            <p class="mb-2">e) If you are working towards an exam, the relevant examining body will be told what has happened in accordance with the examination board’s policy</p>
            <p class="mb-2">f) External examiners/verifiers will also be told what you have done, in accordance with the examining board’s policy</p>
            <p class="mb-2">When what you have done is thought to be a moderate case of academic misconduct –</p>
            <p class="mb-2">a) Your mark or assessment grade may be reduced or you may be awarded zero / referral, depending on how serious what you have done appears to the BERKELEY</p>
            <p class="mb-2">b) You may not be allowed to take the unit/exam/test again</p>
            <p class="mb-2">c) The Course Leader or Curriculum Manager may decide that you must attend a second stage interview. If this has happened before you may go straight to a third stage interview</p>
            <p class="mb-2">d) The relevant examining body will be told what you have done, in accordance with the examination board’s policy</p>
            <p class="mb-2">e) External examiners/verifiers will also be told what you have done, in accordance with the examining board’s policy</p>
            <p class="mb-2">When what you have done is thought to be a serious case of academic misconduct –</p>
            <p class="mb-2">
                a) A third stage Code of Conduct meeting will be convened by the Vice Principal Students and Curriculum. A sanction will be awarded. This will be decided by the BERKELEY staff interviewing you and will depend on the seriousness of
                what you have done. Any of the following may be given –
            </p>
            <p class="mb-2">• A zero or referral grade in the exam/test/unit is given or the assessed work is not awarded a grade.</p>
            <p class="mb-2">• You are not allowed to re-sit the exam or test, or you are not allowed to re-do the piece of assessed work.</p>
            <p class="mb-2">• You are disqualified from your course.</p>
            <p class="mb-2">• You are permanently or temporarily excluded from the BERKELEY.</p>
            <p class="mb-2">b) The relevant examining body will be told what you have done, in accordance with the examining board’s policy.</p>
            <p class="mb-2">c) External examiners/verifiers will also be told what you have done, in accordance with the examining board’s policy</p>
            <p class="mb-2">
                In all cases, a note will be made on your file of the allegation, the outcome and any sanction you are given. You need to know that this information may be used by the BERKELEY when it is asked to provide a reference for you, for
                example if you want to go to another BERKELEY or get a job.
            </p>
            <p class="mb-2">All students must sign the awarding body document confirming that the work they have submitted is their own and that they have correctly referenced any sources of information</p>
            <h2 class="text-xl  mb-2"><strong>5. PROCEDURE TO DEAL WITH STUDENT ACADEMIC MISCONDUCT FOR STAFF: – GUIDELINES ON DEFINITIONS*</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">
                The initial investigation will be undertaken by the Course Leader or a nominated member of teaching staff with findings reviewed by Curriculum Manager before any actions at Stages 2 or 3 are taken. Provision must be made for
                consultation with more senior staff in the case of a moderate or severe case being suspected.
            </p>
            <p class="mb-2">The allegations against the student should be provided to the student in writing before any action is taken, so that the student is clear of the case to be answered. Copies of relevant work and staff notes should be retained.</p>
            <p class="mb-2">Examples of minor cases could include a student:</p>
            <p class="mb-2">a) Receiving undue help in good faith because instructions have been misunderstood.</p>
            <p class="mb-2">b) Copying a couple of sentences or using someone else’s diagrams.</p>
            <p class="mb-2">c) Copying small amounts of text from books without direct acknowledgement, but which does not make a significant contribution to the overall work</p>
            <p class="mb-2">d) Downloading from the internet without acknowledgement, using another’s digital storage or copying work from another’s digital storage.</p>
            <p class="mb-2">e) Using another’s artwork</p>
            <p class="mb-2">f) Not referencing work properly.</p>
            <p class="mb-2">g) Failing to acknowledge the source of a small section of an assignment.</p>
            <p class="mb-2">h) Infringing the policy when the assessed work does not contribute to the final grade. Examples of moderate cases could include:</p>
            <p class="mb-2">• Copying from books without acknowledgement which has the effect of making a significant contribution to the overall work</p>
            <p class="mb-2">• Limited plagiarism from professional work (not course books).</p>
            <p class="mb-2">• Limited copying of other candidates work (hard copy or from digital storage), or excessive help within one piece of work.</p>
            <p class="mb-2">• Limited downloading of information from the internet</p>
            <p class="mb-2">• Planned collusion with others</p>
            <p class="mb-2">• The use of model answers downloaded from the internet</p>
            <p class="mb-2">• Failure to keep to described limitations in exam or controlled assessment preparation, such as preparing lengthy scripts or paragraphs where only bullet points are permitted</p>
            <p class="mb-2">• In a situation where the assessed work contributes to final grade.</p>
            <p class="mb-2">• Repeated minor cases.</p>
            <p class="mb-2">Examples of serious cases could include:</p>
            <p class="mb-2">a) Extensive copying of textbooks in one piece of work or limited copying in two or more pieces of work which makes a significant contribution to the work/s.</p>
            <p class="mb-2">b) Extensive plagiarism of professional works (more than 100 words)</p>
            <p class="mb-2">c) Buying, selling or stealing of work.</p>
            <p class="mb-2">d) Repeated evidence of extensive use of information from the internet without acknowledgement</p>
            <p class="mb-2">e) Using model internet answers</p>
            <p class="mb-2">f) Using past candidates’ work from previous courses/years.</p>
            <p class="mb-2">g) Undue help from inside or outside of the centre. h) Repeated moderate cases.</p>
            <h2 class="text-xl  mb-2"><strong>6. ACTION TO BE TAKEN BY STAFF IF ACADEMIC MISCONDUCT IS BELIEVED TO BE PROVEN BEYOND REASONABLE DOUBT WITH DIRECT REFERENCE TO THE PARENT TEXT OR OTHER EVIDENCE, AND/OR IS ADMITTED BY STUDENT</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">If the student admits misconduct:</p>
            <p class="mb-2">Arrange a meeting with the student to hear his/her comments. The investigating member of staff determines the level of seriousness of the incident and considers the appropriate action.</p>
            <p class="mb-2">If a minor case is identified, the investigating member of staff may choose one or more of the following at their discretion –</p>
            <p class="mb-2">a) Discuss the incident with the student in a tutorial.</p>
            <p class="mb-2">b) Verbally warn the student about future conduct in writing with a note retained on student file</p>
            <p class="mb-2">c) Deduct marks from the student’s work however be done in the case of criterion referenced courses), or return work to be re-done and resubmitted for marking</p>
            <p class="mb-2">d) If this has happened before, refer directly to a 2nd Stage interview e) Inform the examining body, in line with their procedures</p>
            <p class="mb-2">f) Inform external examiners/verifiers in line with examining body’s procedures</p>
            <p class="mb-2">If a moderate case is identified, the investigating staff (usually the Curriculum Manager or other nominated manager) may elect to</p>
            <p class="mb-2">a) Award a mark which may be on a scale between a minimum pass mark only and a zero grade, or similarly reduce the assessment grade (courses other than criterion referenced)</p>
            <p class="mb-2">b) Give the student a written warning (In line with stage 2 in the code of conduct)</p>
            <p class="mb-2">c) Withdraw the right of the student to re-sit an exam/test or resubmit an assessed piece of work</p>
            <p class="mb-2">d) Refer the case immediately to a 3rd Stage interview (at the discretion of the investigating member of staff).</p>
            <p class="mb-2">e) Notify the examining body, in line with their procedures</p>
            <p class="mb-2">f) Inform external examiners/verifiers in line with examining body’s procedures</p>
            <p class="mb-2">If a serious case is identified, staff should immediately refer to the Director of Students Quality and Curriculum for a third stage interview meeting which can:</p>
            <p class="mb-2">a) Award a zero grade in the exam/test/module, or withhold from awarding a grade for assessed work</p>
            <p class="mb-2">b) Withdraw the right of the student to re-sit the exam or test, or withdraw the right to resubmit work for assessment.</p>
            <p class="mb-2">c) Give the student a final written warning d) Disqualify the student from the course</p>
            <p class="mb-2">d) Recommend temporary or permanent exclusion of the student from the BERKELEY.</p>
            <p class="mb-2">e) Inform the examining body, in line with their procedures</p>
            <p class="mb-2">g) Inform external examiners/verifiers, in line with their procedures</p>
            <p class="mb-2">
                In all cases, a note of the allegation/s, outcome and action taken should be recorded on the student’s file. Students should be aware that notes on a student’s file might be drawn on, in the event of the BERKELEY being asked to
                provide a reference for the student.
            </p>
            <h2 class="text-xl  mb-2"><strong>7. ACTION BY STAFF, IF ACADEMIC MISCONDUCT IS NOT PROVEN YET STILL SUSPECTED, OR IF THE MISCONDUCT IS PROVEN, YET NOT ADMITTED BY THE STUDENT THERE WILL BE THE NEED TO INVESTIGATE, AS DESCRIBED ABOVE</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">If a minor case is alleged – Student attends a 1st Stage Interview – the process</p>
            <p class="mb-2">a) Interview will be chaired by the Course Leader or nominated member of staff, and attended by relevant teacher/s and student.</p>
            <p class="mb-2">b) Written notice to the student of the nature of interview and allegations should be sent prior to the interview, notifying them that they can bring a friend, relative or a student adviser to the meeting for support.</p>
            <p class="mb-2">c) The incident is discussed with the student, with the evidence and location/s in the student’s work being identified.</p>
            <p class="mb-2">d) The student is questioned, to test knowledge of the work.</p>
            <p class="mb-2">e) The student has an opportunity to explain.</p>
            <p class="mb-2">f) The tutor listens to each case carefully and makes a decision.</p>
            <p class="mb-2">Possible outcomes</p>
            <p class="mb-2">a) No academic misconduct has taken place and the assignment remains marked as it stands.</p>
            <p class="mb-2">b) The student accepts that academic misconduct has taken place and is allowed to redo and resubmit the work and is awarded the minimum pass mark. A verbal warning is issued.</p>
            <p class="mb-2">
                c) The student accepts that academic misconduct has taken place and accepts a reduced mark guide 6% (this cannot however be done in the case of criterion referenced courses) and a report is made to the external examiner/verifier. A
                verbal warning is issued.
            </p>
            <p class="mb-2">d) The student denies academic misconduct has occurred and a 2nd Stage interview is necessary.</p>
            <p class="mb-2">e) The BERKELEY informs external examiners/verifiers in line with the examining board’s procedures.</p>
            <p class="mb-2">If a moderate case is alleged</p>
            <p class="mb-2">A report is made by Course Leader or Academic Tutor who will refer to their Curriculum Manager to start the Code of Conduct investigation and procedures.</p>
            <p class="mb-2">Student attends a 2nd Stage Interview – the process</p>
            <p class="mb-2">a) The interview will be chaired by the Curriculum Manager and attended by the relevant teacher/s and student.</p>
            <p class="mb-2">b) Written notice to the student of the nature of interview and the allegations should be sent prior to the interview, notifying them that they can bring a friend, relative or a student advisor to the meeting for support.</p>
            <p class="mb-2">c) The incident is discussed with the student, with evidence and the location/s in the student’s work identified.</p>
            <p class="mb-2">d) The student is questioned, to test knowledge of the work.</p>
            <p class="mb-2">e) The student has an opportunity to explain.</p>
            <p class="mb-2">f) The manager listens to each case carefully and makes a decision.</p>
            <p class="mb-2">Possible outcomes</p>
            <p class="mb-2">a) No academic misconduct has taken place and the assignment remains marked as it stands.</p>
            <p class="mb-2">
                b) The meeting accepts that academic misconduct has taken place and the student is allowed to redo and resubmit the work and is awarded no more than the minimum pass mark (courses other than criterion referenced). A formal written
                warning is issued and if the offence is repeated, this constitutes misconduct, requiring an automatic third stage interview.
            </p>
            <p class="mb-2">c) The student denies academic misconduct has occurred. Appeal to a 3rd Stage interview d) The BERKELEY informs external examiners, in line with their procedures</p>
            <p class="mb-2">e) The BERKELEY informs the awarding body, external examiners/verifiers in line with the examining board’s procedures.</p>
            <p class="mb-2">If a serious case of academic misconduct and malpractice is alleged –</p>
            <p class="mb-2">A report made by Course Leader or Academic Tutor who will start Code of Conduct procedures.</p>
            <p class="mb-2">Student attends a 3rd Stage Interview –</p>
            <p class="mb-2">a) Chaired by Vice Principal Students and Curriculum, attended by relevant teachers and student.</p>
            <p class="mb-2">b) Written notice to student of the nature of the interview and allegations should be sent prior to the interview, notifying them that they can bring a friend, relative or a student advisor to the meeting for support.</p>
            <p class="mb-2">c) Incident discussed with evidence and location in student’s work of plagiarism.</p>
            <p class="mb-2">d) Student is questioned, to test his/her knowledge of the work.</p>
            <p class="mb-2">e) The student has an opportunity to explain.</p>
            <p class="mb-2">f) The manager listens to each case carefully and makes a decision.</p>
            <p class="mb-2">Possible outcomes</p>
            <p class="mb-2">
                a) No academic misconduct has taken place and the assignment remains marked as it stands. b) The student accepts that academic misconduct has taken place. A zero grade in the exam/test module is given, or the assessed work is not
                awarded a grade. Neither a re-sit, nor re-doing and re-presenting coursework is allowed. The student is issued with a final written warning or is disqualified from the course.
            </p>
            <p class="mb-2">c) The student denies academic misconduct has occurred and an Appeal to the Principal takes place.</p>
            <p class="mb-2">d) The BERKELEY informs external examiners, in line with their procedures.</p>
            <p class="mb-2">c) The BERKELEY informs, awarding body, external examiners/verifiers in line with the examining board’s procedures.</p>
            <p class="mb-2">
                In all cases, a note of the allegation/s, outcome and action taken will be recorded on the student’s file. Students should be aware that notes on a student’s file might be drawn on, in the event of the BERKELEY being asked to provide
                a reference for the student.
            </p>
            <p class="mb-2">3rd Stage Interview – the process</p>
            <p class="mb-2">a) Chaired by Center Manager Students and Curriculum and attended by Course Leader, assessing teacher and student with student advisor or family/friend support.</p>
            <p class="mb-2">b) Written notice is sent to the student stating the allegations, a summary of the evidence, the time and place and possible outcomes, and allowing them to bring a friend, relative or learning mentor for support.</p>
            <p class="mb-2">c) Copies of any documents, to be considered or relied upon by any of the parties, should be disclosed to the student with the written notice if possible but in any event at least three clear working days before the interview.</p>
            <p class="mb-2">d) A formal record of the interview is made, with the assessing teacher presenting the case and allegations of academic misconduct.</p>
            <p class="mb-2">e) The student states their case.</p>
            <p class="mb-2">f) The Center Manager Students and Curriculum or other member of staff as appropriate questions the student to test their knowledge of the work</p>
            <p class="mb-2">g) The Center Manager Students and Curriculum reaches a decision.</p>
            <p class="mb-2">Possible outcomes</p>
            <p class="mb-2">a) No academic misconduct has taken place and the assignment is marked as it stands.</p>
            <p class="mb-2">
                b) The assessing course teacher’s decision is upheld and the work is assigned a zero mark. The examining body is informed. The examining body will review this and decide whether the student should progress or whether they are awarded
                any credits based on previously assessed work.
            </p>
            <p class="mb-2">c) The student is found in breach of the Code of Conduct and a recommendation for temporary or permanent exclusion is made in writing.</p>
            <p class="mb-2">d) The BERKELEY informs external examiners, in line with their procedures e) The BERKELEY informs external examiners/verifiers if appropriate</p>
            <h2 class="text-xl  mb-2"><strong>8. APPEALS</strong></h2>
            <p class="mb-2">
                These will be dealt with in line with the BERKELEY’s Student Code of Conduct Procedure – Appeals. Should a Higher Education student be dissatisfied with the implementation of the procedures and processes followed once all avenues to
                explore this have been followed, they may take their concerns to the Office.
            </p>
            <p class="mb-2">
                You normally need to have completed the Academic Misconduct and Malpractice procedures and the Student Code of Conduct Appeals procedure before you complain. BERKELEY will send you a letter called a “Completion of Procedures Letter”
                when you have reached the end of our processes and there are no further steps you can take internally. If your appeal is not upheld we will issue you with a Completion of Procedures Letter automatically. If your appeal is upheld or
                partly upheld you can ask for a Completion of Procedures Letter if you want one. You can find more information about Completion of Procedures Letters and when you should expect to receive one here.
            </p>
            <h2 class="text-xl  mb-2"><strong>9. BERKELEY ACADEMIC MISCONDUCT AND MALPRACTICE POLICY – AS IT APPLIES TO STAFF</strong></h2>
            <p class="mb-2"></p>
            <p class="mb-2">The staff Disciplinary Policy and its associated procedures will be applied if a member of staff is implicated in a case of academic misconduct and malpractice. Situations where a member of staff may be implicated may include:</p>
            <p class="mb-2">• Where the member of staff has produced part or all of the work submitted and not declared this;</p>
            <p class="mb-2">• Where there is fabrication of grades achieved that is not supported by the evidence in the work submitted.</p>
            <p class="mb-2">• Where a teacher goes beyond guiding the student on what is required in a piece of work and tells the student specifically what to write or procedure of making/producing.</p>
            <p class="mb-2">• Where a member of staff has assessed, internally verified, moderated, invigilated, read, scribed or quality assured work from a student where they have a vested interest in the achievement of that student.</p>
            <hr />
            <h1><strong>ACADEMIC MISCONDUCT POLICY – FOR STUDENTS</strong></h1>
            <p class="mb-2"></p>
            <p class="mb-2">The following are dishonest and therefore unacceptable and not allowed by the BERKELEY –</p>
            <p class="mb-2">• Taking someone else’s work, images or ideas and passing it off as your own (This is called plagiarism),</p>
            <p class="mb-2">• Using the computer, either the internet, or information stored on a hard or floppy disk which belongs to someone else, and passing it off as your own</p>
            <p class="mb-2">• Cheating, that is, acting unfairly or dishonestly to gain an advantage</p>
            <p class="mb-2">• Secretly agreeing with others to cheat or deceive. (This is known as collusion)</p>
            <p class="mb-2">• Collaborating with other students to pass off collectively produced work as one’s own, beyond or outside any request by teaching staff for groups of students to collaborate on projects or assignments. This is known as syndication.</p>
            <p class="mb-2">
                All these are called academic misconduct or malpractice. If you are discovered or suspected of doing any of the things shown in the list above, the BERKELEY will investigate and may take action against you. (That is, you will be
                subject to Code of Conduct procedures.)
            </p>
            <p class="mb-2">This is what is expected of you whilst you are at the BERKELEY-</p>
            <p class="mb-2">i) You will only hand in your own original work for assessment.</p>
            <p class="mb-2">
                ii) When you have used information provided by someone else you will acknowledge this by giving the person’s name and where you found the information in your work (or in your portfolio) as you go along. For example, if you use someone
                else’s words, you will enclose the quote with inverted commas.
            </p>
            <p class="mb-2">iii) You will show when you have downloaded information from the internet</p>
            <p class="mb-2">
                iv) You will never use another’s digital storage as if it is your own work, nor copy work from digital storage belonging to someone else and use it as if it were your own. Digital storage may include pen drives / memory sticks, SD cards
                from cameras or phones, portable hard drives, DVDs or shared folders from the BERKELEY or other networks.
            </p>
            <p class="mb-2">v) You will never use someone else’s artwork, pictures or graphics (including graphs, spreadsheets etc.) as if they were made by you</p>
            <p class="mb-2">
                vi) You will never let other students use or copy from your work and pass it off as if they had done it themselves vii) You can expect all cases of suspected academic misconduct and malpractice to be fully investigated using the
                BERKELEY Code of Conduct procedures. If proved, you can expect the BERKELEY to take disciplinary action against you. What happens will depend on how serious what you have done appears to the BERKELEY.
            </p>
            <p class="mb-2">
                viii) The member of staff who has looked into what you have done will decide how serious the case appears at first. This person will consult with senior colleagues when a moderate or serious case is suspected. The claims that you have
                done something illegal or wrong (the allegations) will be written down so that you know the case you have to answer.
            </p>
            <p class="mb-2">The actions taken by the BERKELEY, if they believe from the evidence you have done something wrong, may include the following:</p>
            <p class="mb-2">When what you have done is thought to be a minor case of academic misconduct-</p>
            <p class="mb-2">i) What you have done will be discussed with you in a private tutorial with your Course Leader or Academic Tutor.</p>
            <p class="mb-2">ii) You will be given a warning about how you must act in the future.</p>
            <p class="mb-2">iii) You may have marks from your piece of work taken away or you may have work returned to re-do and hand in for remarking.</p>
            <p class="mb-2">iv) If this has happened before, you will go straight to a second stage interview.</p>
            <p class="mb-2">v) If you are working towards an exam, the relevant examining body will be told what has happened in accordance with the examination board’s policy.</p>
            <p class="mb-2">vi) External examiners/verifiers will also be told what you have done, in accordance with the examining board’s policy.</p>
            <p class="mb-2">When what you have done is thought to be a moderate case of academic misconduct-</p>
            <p class="mb-2">i) Your mark or assessment grade may be reduced or you may be awarded zero/ referral, depending on how serious what you have done appears to the BERKELEY</p>
            <p class="mb-2">ii) You may not be allowed to take the unit/exam/test again</p>
            <p class="mb-2">iii) The Course Leader or Curriculum Manager may decide that you must attend a second stage interview. If this has happened before you may go straight to a third stage interview</p>
            <p class="mb-2">iv) The relevant examining body will be told what you have done, in accordance with the examination board’s policy</p>
            <p class="mb-2">v) External examiners/verifiers will also be told what you have done, in accordance with the examining board’s policy</p>
            <p class="mb-2">When what you have done is thought to be a serious case of academic misconduct-</p>
            <p class="mb-2">
                i) A third stage Code of Conduct meeting will be convened by the Director of Students Quality and Curriculum. A sanction will be awarded. This will be decided by the BERKELEY staff interviewing you and will depend on the seriousness
                of what you have done. Any of the following may be given –
            </p>
            <p class="mb-2">• A zero or referral grade in the exam/test/unit is given or the assessed work is not awarded a grade.</p>
            <p class="mb-2">• You are not allowed to re-sit the exam or test, or you are not allowed to re-do the piece of assessed work.</p>
            <p class="mb-2">• You are disqualified from your course.</p>
            <p class="mb-2">• You are permanently or temporarily excluded from the BERKELEY.</p>
            <p class="mb-2">ii) The relevant examining body will be told what you have done, in accordance with the examining board’s policy</p>
            <p class="mb-2">iii) External examiners/verifiers will also be told what you have done, in accordance with the examining board’s policy</p>
            <p class="mb-2">
                In all cases, a note will be made on your file of the allegation, the outcome and any sanction you are given. You need to know that this information may be used by the BERKELEY when it is asked to provide a reference for you, for
                example if you want to go to another BERKELEY or get a job.
            </p>
            <p class="mb-2">You must sign the statement below to show that you have read and understood the BERKELEY rules on academic misconduct as they are shown on this paper.</p>
            <p class="mb-2">
                “I agree that I have read and understood the BERKELEY Policy on Academic Misconduct. I understand that if I cheat and present others’ work as my own, without showing who did the work and where I found it, the BERKELEY will take
                action.
            </p>
            <p class="mb-2">I agree that all the work I hand in during my course or put in my portfolio will be entirely my own, unless I show clearly in my work where I have used someone else’s work, have worked with someone else, or have received help.”</p>

            
        </div>

    </main>

    </div>
</section>

@endsection

@push('script')
    
@endpush