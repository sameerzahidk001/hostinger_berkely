@extends('layouts.app')
@push('style')

@endpush

@section('content')

<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">General Policy</span>
            
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">General Policy

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
            <h3 class="text-xl font-bold mb-2">POLICIES & PROCEDURES</h3>
            <h3 class="text-xl font-bold mb-2">Welcome!</h3>
            <p class="mb-4">Welcome to BERKELEY. Our commitment to research makes us one of the most quality organizations in terms of learning. We look forward to the future with an eagerness to continue our pursuit of excellence. We wish you a productive and enjoyable career at the BERKELEY. This Handbook provides a general overview for all staff.</p>
            <h3 class="text-xl font-bold mb-2">Important Contacts</h3>
            <p class="mb-4">Please contact administration department to get the list of important contacts.</p>
            <h3 class="text-xl font-bold mb-2">Getting Started</h3>
            <h3 class="text-xl font-bold mb-2">Maps</h3>
            <p class="mb-4">Maps of the BERKELEY buildings and the town can be found on location page.</p>
            <h3 class="text-xl font-bold mb-2">Induction and New Staff Essentials</h3>
            <p class="mb-4">You are asked to attend an ‘all staff’ induction event (held twice a year) where you will have the opportunity to meet senior members of the BERKELEY. In addition, New Staff Essentials courses are held monthly. Your attendance at one of these monthly courses is very important as they include information which the BERKELEY is under a legal obligation to provide. To register for these courses and Induction information, please contact Human Resource department.</p>
            <h3 class="text-xl font-bold mb-2">ID Cards</h3>
            <p class="mb-4">A staff ID card is required for identification purposes and for access to various buildings including BERKELEY library. It is also required if you wish to take advantage of offers via the staff discount scheme. New staff should email their ID card photograph ahead of their start date so that it’s ready for their first day of employment; HR will issue the staff ID card on this day. Authorized staff in the Section/ Unit must contact Campus Card Services to arrange specific access for you. If a contract is extended or changed, contact should be made with Campus Card Services to ensure the appropriate access is in place. Contact: Campus Card Services</p>
            <h3 class="text-xl font-bold mb-2">User Account/Email</h3>
            <p class="mb-4">A new staff email account will be created for you ahead of your start date. On the morning of your first day, staff entitlements will automatically be activated in terms of email account & Office 365. Where this automated process is not possible, staff will need to attend the IT Service Desk in the Library to activate their user account, taking with them the staff ID Card for identification purposes. For help with email set-up and Other IT help contact IT department.</p>
            <h3 class="text-xl font-bold mb-2">New Starter Online Training</h3>
            <p class="mb-4">As a new starter at the BERKELEY, there are a number of online courses that you may be required to complete. These courses cover important aspects of compliance with external regulations or law or are important parts of the BERKELEY’s governance processes and policies. Information and register for these courses are available with training department.</p>
            <h3 class="text-xl font-bold mb-2">Structure and Governance</h3>
            <h3 class="text-xl font-bold mb-2">BERKELEY Governance</h3>
            <p>Information on the BERKELEY’s governing bodies and Structure and Governance can be found In director’s office.</p>
            <h3 class="text-xl font-bold mb-2">Principal’s Office</h3>
            <p>Information on personnel in the Principal’s Office can be found With HR Department.</p>
            <h3 class="text-xl font-bold mb-2">Governance Zone</h3>
            <p>All BERKELEY policies, procedures and guidance can be found in director office. All central policies associated with the BERKELEY ’s governance and supporting procedures are now in an authorized, single-source repository, which is supported and proactively developed by IT Services with the policies being linked, as appropriate, to the relevant sections of the BERKELEY website. This means that each policy can be proactively maintained and monitored through, and beyond, its lifecycle, ensuring its currency, validity, and coverage in a consistent way. Policies are now easier to find and access both by users and by owners/maintainers. Potential gaps and overlaps are easier to identify, and the BERKELEY is able to demonstrate improved legal compliance with statutory requirements. As mentioned, the Governance Zone is now the single authoritative source of policies– if you possess or know about any older versions of policies, please remove them. Likewise, if you own a policy, guideline or a similar document that you believe should be in the Governance Zone, please email your request to director office. Finally, the Digital Communications Team has created re-directs from policies’ operational sites; however, if any policy has slipped through the net and you identify any broken links, we would be grateful if you inform the Digital Communications team via IT help desk, who will deal with the issue appropriately.</p>
            <h3 class="text-xl font-bold mb-2">Strategic Plan</h3>
            <h3 class="text-xl font-bold mb-2">The Planning Office ensures coherence and collaboration across the institution to inform and strengthen strategic decision making. We provide professional expertise for planning, external policy, and data analysis, management information provision, timetabling, and key performance indicators risk management:</h3>
            <h3 class="text-xl font-bold mb-2">Registry</h3>
            <p class="mb-4">The registry is one of the central academic administrative Units for the BERKELEY. Registry staff manage student records from the point of application to graduation for all main student cohorts: undergraduate, postgraduate taught, postgraduate research, International Education Institute, Summer Sections and courses. This remit encompasses:</p>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                        Curriculum management
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                        Collaborations and study abroad
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                        Examinations
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                        Tuition fees
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Module results
                    </p>
                </li>
            </ul>
            <h3 class="text-xl font-bold mb-2">Important Dates</h3>
            <h3 class="text-xl font-bold mb-2">The Academic Calendar</h3>
            <p class="mb-4">Academic calendar can be found in time table section.</p>
            <h3 class="text-xl font-bold mb-2">Events Calendar</h3>
            <p class="mb-4">All BERKELEY events for staff, students, and the general public are listed in the online Events Calendar.
                To submit information for consideration on the Calendar check website.</p>
            <h3 class="text-xl font-bold mb-2">IT Services – here to help</h3>
            <p class="mb-4">We recognise that IT is likely to be essential to allow you to perform your role at the BERKELEY . IT Services offer a wide range of services and support.</p>
            <h3 class="text-xl font-bold mb-2">User Account and Email</h3>
            <p class="mb-4">A new staff email account will be created for you ahead of your start date. On the morning of your first day, staff entitlements will automatically be activated in terms of email account, Office 365, printing and Library borrowing. Where this automated process is not possible, staff will need to attend the IT Service Desk in the Library to activate their user account, taking with them the staff ID Card for identification purposes. If you require access to your department’s shared email account, your line manager will arrange this for you. The IT Service Desk will send you instructions on how to set the account up in Outlook.</p>
            <h3 class="text-xl font-bold mb-2">File storage</h3>
            <p class="mb-4">Save your personal files in your home drive or in OneDrive. Your home drive allows you 5GB of storage on the BERKELEY network which is backed up three times daily. Your Office 365 account gives you 1TB of storage through OneDrive, which you can access from any device. If your Section or Professional Service Unit has shared drives for departmental files, your line manager will arrange for you to get access.</p>
            <h3 class="text-xl font-bold mb-2">Wi-Fi</h3>
            <p class="mb-4">There is wireless access across more than 95% of BERKELEY buildings. All IT queries, problems or requests, should be directed to the IT Service Desk.</p>
            <h3 class="text-xl font-bold mb-2">Front Desk: Main Library:</h3>
            <p class="mb-4">You will find details about a range of services from IT department.</p>
            <h3 class="text-xl font-bold mb-2">VPN:</h3>
            <p class="mb-4">When you are away from the BERKELEY, some resources will be restricted, but you can still access BERKELEY resources by using the Virtual Private Network.</p>
            <h3 class="text-xl font-bold mb-2">Microsoft Office 365:</h3>
            <p class="mb-4">BERKELEY staff can install Microsoft Office on up to five of their personal devices. This will already be installed on BERKELEY supplied devices.</p>
            <h3 class="text-xl font-bold mb-2">Security:</h3>
            <p class="mb-4">Staff members are responsible for safeguarding their account and data by not sharing passwords and reporting phishing emails to it@edu.BERKELEY.org so our IT Security team can investigate.</p>
            <h3 class="text-xl font-bold mb-2">Service Status:</h3>
            <p class="mb-4">The status of different BERKELEY systems are indicated on the Service Status page. If a service doesn’t appear to be working, we recommend that you check this page first.</p>
            <h3 class="text-xl font-bold mb-2">Password Self Service:</h3>
            <p class="mb-4">Please register for this service. Should you ever forget your password, you can use this facility to reset your password.</p>
            <h3 class="text-xl font-bold mb-2">Printing:</h3>
            <p class="mb-4">All Sections and Professional Service Units have a UniPrint device(s) installed. UniPrint is a ‘pull print system’ which holds your print job(s) in a print queue. You can retrieve your print job(s) from any public printer by logging into the printer with your ID card. You can also print from your laptop.</p>
            <h3 class="text-xl font-bold mb-2">Work telephones:</h3>
            <p class="mb-4">If you have any queries about your office desk phone or work mobile phone, please contact the Telephone Office.</p>
            <h3 class="text-xl font-bold mb-2">Consumer Protection</h3>
            <p>We are in strict compliance of the Consumer Protection Act by ensuring that contracts between consumers and suppliers are fair and transparent. The most significantly we are doing the following:</p>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Provide all material information to allow students to make informed choices about their BERKELEY and course;
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Ensure that BERKELEY terms and conditions are fair and accessible, with unusual terms clearly communicated to students;
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Adopt a fair and transparent complaints procedure which students understand.
                    </p>
                </li>
                
            </ul>
            <p class="mb-4">The BERKELEY has always aimed to provide an excellent academic experience and been committed to ensuring a good relationship with our students. We adopt the principles of fairness, clarity, and transparency whilst safeguarding academic standards and the student experience within the BERKELEY.</p>
            <h3 class="text-xl font-bold mb-2">Technology Enhanced Learning and IT Training</h3>
            <p class="mb-4">If you are a new member of the teaching staff you will be expected to use the BERKELEY ’s IT Environment. There are two main web-based systems – LMS and Module Management System (MMS) which are integrated through the gateway to web-based services. You will be able to log in to the integrated site or both of these individual systems with your BERKELEY.</p>
            <h3 class="text-xl font-bold mb-2">Other Technologies</h3>
            <p class="mb-4">We provide training and support for a wide range of institutional learning technologies, including Lecture Capture, Plagiarism Detection and Clickers. We also train and advise on every aspect of using technology in your teaching, whether online or in the classroom.</p>
            <p class="mb-4">All our workshops can be booked through the BERKELEY ’s Personal Development Management System (PDMS) under the Technology Enhanced Learning category. We are also happy to provide bespoke sessions for Sections or teaching teams and one-to-one support.</p>
            <h3 class="text-xl font-bold mb-2">Contact us</h3>
            <p class="mb-4">You can keep up to date with what’s happening on:</p>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Our blog
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Twitter
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Facebook
                    </p>
                </li>
                
            </ul>
            <p class="mb-4">Or contact us via info@edu.BERKELEY.org. For full details on the course including how to register, see our website.</p>
            <h3 class="text-xl font-bold mb-2">Information Governance and Security</h3>
            <p class="mb-4">Information is one of the BERKELEY’s most valuable resources and as a member of staff, you have a role to play in how effectively this is managed. There are also, certain statutory obligations which drive the BERKELEY’s governance arrangements in the areas of data protection, cybersecurity and freedom of information.</p>
            <h3 class="text-xl font-bold mb-2">Data Protection</h3>
            <p class="mb-4">The protection of privacy and providing for individual’s rights in the collection and use of their personal data is established through law. The BERKELEY collects and makes use of a wide range of personal data for students, staff, and others who interact with us. It is important that when collecting personal data only the minimum amount of information necessary to complete a task/activity is collected. This personal data should then only be used for the purposes for which it was collected and all reasonable steps taken to secure and maintain the confidentiality of that data.</p>
            <p class="mb-4">All members of staff should familiarise themselves with the ‘core’ privacy notices that the BERKELEY publishes and maintains; these set out what student and staff personal data are collected, the purposes and uses that will be made of this and with whom personal data may be shared. Those documents are in effect promises to students and staff; being familiar with how personal data are to be used will help the BERKELEY to meet its stated commitments.
                Data protection laws also give a range of rights to individuals. Those rights are qualified and in some instances depending on the BERKELEY ’s basis for making use of personal data they may not apply. For example, if the BERKELEY has a legal requirement to collect information then many of the data protection rights will not be available.</p>
            <h3 class="text-xl font-bold mb-2">Corporate Communications</h3>
            <p class="mb-4">Corporate Communications is one of the central administrative services for the BERKELEY. Corporate Communications is responsible for managing and coordinating the BERKELEY ’s press and media relations, public affairs, community relations and public engagement, brand identity, marketing, web content, new media, social media, print and design, and internal communications.</p>
            <p class="mb-4">The digital communications team can help with:</p>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Online accessibility
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Training courses as part of the BERKELEY’s ‘Digital Visa’
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Social media guidance
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Website support and maintenance
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Usability testing.
                    </p>
                </li>
                
            </ul>
            <h3 class="text-xl font-bold mb-2">The Common Room</h3>
            <p class="mb-4">The BERKELEY Common room supports teaching and research by providing digital and print resources and managing study spaces, our team is committed to providing excellent customer service.</p>
            <h3 class="text-xl font-bold mb-2">Support and Development</h3>
            <p class="mb-4">BERKELEY ’s VC for Academic, Professional, and Organisational Development. It aims to promote an integrated personal, professional, and academic development service to all staff and students.</p>
            <h3 class="text-xl font-bold mb-2">Staff</h3>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Academic Staff Development Course
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    IT Training
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Passport to Excellence schemes
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Module Evaluation Questionnaire Service
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Learning Technology Support
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Organisational Development
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Mentoring and Coaching Schemes
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Open course of courses
                    </p>
                </li>
                
            </ul>
            <h3 class="text-xl font-bold mb-2">Students</h3>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Study Skills Support
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Work and Career Skills
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Professional Skills Curriculum (PSC)
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Academic Study Skills Workshops
                    </p>
                </li>
                
            </ul>
            <h3 class="text-xl font-bold mb-2">Career Support</h3>
            <p class="mb-4">The Careers Centre offers its services to staff members (graduate level), of the BERKELEY (where we are able to help, bearing in mind that our core expertise is with immediate graduate labor markets and further study options Dedicated careers advisers offer specialist advice to early-career research staff.</p>
            <h3 class="text-xl font-bold mb-2">Lifelong and Flexible Learning</h3>
            <p class="mb-4">The Lifelong and Flexible Learning team within Admissions offers certification on a part-time basis attending classes delivered entirely in the evening and taught by some of our leading academic staff. Staff can enroll for a full course or choose to take standalone modules as part of your professional development. The course is open to all staff members, their families and friends. To find out more about the course, contact.</p>
            <h3 class="text-xl font-bold mb-2">Mentoring and Coaching</h3>
            <p class="mb-4">The BERKELEY has several mentoring and coaching schemes for both academic and professional/support staff.</p>
            <h3 class="text-xl font-bold mb-2">Wellbeing</h3>
            <p class="mb-4">The BERKELEY recognizes that it has a moral and legal duty to support the wellbeing of all the people that work with the BERKELEY; staff and learners alike. All the BERKELEY’s institution-wide health and wellbeing activities and initiatives are directed and implemented through the BERKELEY’s Wellbeing. Each month, a different health and wellbeing theme that is relevant to the BERKELEY ’s population is promoted through an information campaign with accompanying activities and courses to develop awareness.</p>
            <h3 class="text-xl font-bold mb-2">BERKELEY wellbeing related policies – Community and Volunteering:</h3>
            <p class="mb-4">The BERKELEY has formed contacts with local volunteering groups, who are all very keen to speak to any staff members who may be interested. There is a wide range of opportunities to get involved within the community. As well as internal options (within the BERKELEY ), there is also a range of external organizations that are grateful for staff volunteers helping out. Any volunteer work can be arranged to fit in with your lifestyle and needs.</p>
            <h3 class="text-xl font-bold mb-2">Health and Safety</h3>
            <p class="mb-4">The Environmental Health and Safety Services Unit ensures, so far as is reasonably practicable, a safe working environment and also safe processes within the BERKELEY. It provides advice on biological agents, chemical risks, fieldwork risks, fire risks, radiation risks, and any other general health and safety issues. The BERKELEY’s Health and Safety service will also undertake accident investigations. To raise a safety issue, please contact the Director of the Unit.</p>
            <h3 class="text-xl font-bold mb-2">The Occupational Health Unit</h3>
            <p class="mb-4">The full-time Occupational Health Adviser is a qualified General and Occupational Health Nurse. The Unit assesses the effect of work on an individual’s health and the effect of an individual’s health on their work.</p>
            <h3 class="text-xl font-bold mb-2">Emergency Procedures</h3>
            <p class="mb-4">All emergency procedures can be found online. In the event of any incident, criminal or otherwise, which may put the safety of the BERKELEY community and/or property at risk, the following action should be taken without delay: First, telephone the police by dialing. Contact the BERKELEY Security and Response Team for incident reporting and emergency support 24/7</p>
            <h3 class="text-xl font-bold mb-2">No Smoking Policy</h3>
            <p class="mb-4">It is the policy of the BERKELEY that all workplaces are smoke-free, and all employees have a right to work in a smoke-free environment.
                The No Smoking Policy is applied fairly and uniformly throughout the BERKELEY and is applicable to all members of staff. It also applies to students, visitors, clients, contractors and all others who use BERKELEY facilities.</p>
            <h3 class="text-xl font-bold mb-2">Alcohol, Drugs, and Substances</h3>
            <p class="mb-4">The BERKELEY recognizes that its primary responsibility is to ensure a safe, healthy, and productive environment for all employees, students, and visitors. This can be put at risk by the excessive and/or inappropriate use by employees of alcohol, drugs or substances, whether illicit, prescribed or over the counter, in such a way that their health, work performance, work environment, and/or conduct of relationships are adversely affected. The BERKELEY encourages employees with alcohol, drug and substance-related problems to seek help.</p>
            <p class="mb-4">Guidance and information are contained within the Policy and Guidance on the Use of Alcohol, Drug, or Substances.
                The BERKELEY has worked to develop guidance, procedures, and training to meet this duty predicated on a safeguarding approach, which is the overarching the ethos of the Government’s Prevent strategy.</p>
            <h3 class="text-xl font-bold mb-2">Security and Response Team</h3>
            <p class="mb-4">Responsibility for security and personal safety rests with all persons who study, work at, reside in, or visit the BERKELEY. However, should you require help or wish to report an incident the BERKELEY’s Security and Response Team is accessible 24/7. It may also be necessary to ensure the Police are alerted by dialing.</p>
            <h3 class="text-xl font-bold mb-2">Environment and Sustainable Development</h3>
            <p class="mb-4">The BERKELEY ’s Environment Team manages the commitments made in the BERKELEY ’s Sustainable Development Policy. We are all responsible for reducing the environmental impacts associated with the day-to-day activities and long term plans of the BERKELEY, including recycling, consumption of energy and water, and sustainable travel.</p>
            <p class="mb-4">We encourage staff to walk, cycle, or use public transport where possible. If you have to bring a car, then you need a permit to park on BERKELEY property.</p>
            <h3 class="text-xl font-bold mb-2">Recycling</h3>
            <p class="mb-4">The BERKELEY is aiming to achieve zero waste status. In order to achieve this goal, we need everyone in the BERKELEY to take initiative when handling potential waste. We also encourage members of staff to use reuse alternatives to skips when cleaning out rooms or buildings of unwanted but still functional items.</p>
            <h3 class="text-xl font-bold mb-2">Business Transformation Portfolio Office</h3>
            <p class="mb-4">The Business Transformation Portfolio Office provides services to support the successful delivery of the BERKELEY ’s business transformation portfolio and its constituent courses and projects.</p>
            <h3 class="text-xl font-bold mb-2">Learning and Teaching</h3>
            <p class="mb-4">For all matters relating to learning and teaching including academic standards and enhancing the student learning experience.</p>

            <h3 class="text-xl font-bold mb-2">Finance</h3>
            <p class="mb-4">Financial Operating Procedures Heads of Sections and Units and all budget holders should be aware of the BERKELEY Financial Operating Procedures, Standing Financial Instructions, and the Scheme of Delegation.</p>
            <p class="mb-4">General information on all money matters such as ordering goods, paying invoices, raising sales invoices, banking cash & cheques can be found. For VAT advice on buying or selling things please contact the tax & treasury team or view the self-help guide at Finance and Support Team.</p>

            <h3 class="text-xl font-bold mb-2">Procurement</h3>
            <p class="mb-4">Procurement’s primary objective is to ensure the BERKELEY is obtaining the best value for money whilst supporting the extensive and wide-ranging requirements of all our teaching and research activities within the BERKELEY. The Procurement Strategy is to efficiently support Decentralised Buying throughout the Sections, Units, and Residences of the BERKELEY.</p>

            <h3 class="text-xl font-bold mb-2">Human Resources</h3>
            <p class="mb-4">Human Resources is responsible for supporting the BERKELEY through the delivery of a comprehensive HR service that develops and adopts relevant people management strategies, provides a range of professional support, information, and expert advice consistent with employment legislation and best practice.</p>
            <p class="mb-4">It is split into five key areas:</p>
            
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Support & Advice (including management information)
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Recruitment & Selection
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Data & Systems
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    Salaries & Pensions
                    </p>
                </li>
                <li class="flex  lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>
                    The Annual Leave Policy
                    </p>
                </li>
                
            </ul>

            <h3 class="text-xl font-bold mb-2">The Annual Leave Policy</h3>
            <p class="mb-4">The annual leave year runs from 1 August to 31 July each year. Holidays not taken by 31 December following the end of the leave year will be lost. The BERKELEY requires staff to retain three days of annual leave for the closure between Christmas and New Year. For more information contact Human Resources.</p>

            <h3 class="text-xl font-bold mb-2">Pay Days</h3>
            <p class="mb-4">You can find the schedule of paydays from the HR department.</p>

            <h3 class="text-xl font-bold mb-2">Sickness Absence Policy</h3>
            <p class="mb-4">The wellbeing of any organization is directly related to the wellbeing of the people who make up the organization. The BERKELEY wishes to develop and maintain policies that provide all members of staff with appropriate support in relation to their health. In the event of illness, follow the procedure below:</p>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Report your absence to your Head of Section/Unit/Designated Officer within one hour of your normal start time on the first day of absence.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Give the reason for your absence.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Keep your Head of Section/Unit/Designated Officer informed if your absence is continuing beyond three calendar days and where possible give a likely date of return.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Provide medical certificates to cover absence over seven calendar days.</p>
                </li>
            </ul>
            <p class="mb-4">For further details relating to long-term illness, you can view the full sickness policy.</p>

            <h3 class="text-xl font-bold mb-2">Adverse Weather Policy</h3>
            <p class="mb-4">The BERKELEY will be deemed to be open unless a specific announcement is publicized via the BERKELEY website, local radio (e.g. Radio BERKELEY, Kingdom FM, Tay FM) or via a message from the CEO’s Office or the Director of Human Resources.</p>

            <h3 class="text-xl font-bold mb-2">Equally Safe</h3>
            <p class="mb-4"></p>

            <h3 class="text-xl font-bold mb-2">Review and Development Processes</h3>
            <p class="mb-4">The BERKELEY currently has separate staff review processes.</p>

            <h3 class="text-xl font-bold mb-2">Equality and Diversity</h3>
            <p class="mb-4">Equality and Diversity in liaison with HR is responsible for ensuring compliance with equalities law including ensuring policies and services are compliant meeting funding expectations, inclusion for people of different backgrounds, signposting to services, and equalities training, with online modules on Diversity and Unconscious Bias available. Equality and Diversity also provides guidance and support on inclusive recruitment and selection, developing an inclusive curriculum, and progress on diversity.</p>

            <h3 class="text-xl font-bold mb-2">Equality, Diversity and Inclusion Policy</h3>
            <p class="mb-4">The BERKELEY is fully committed to respect and fair treatment for everyone, eliminating discrimination, and actively promoting equality of opportunity and delivering fairness to all.</p>

            <h3 class="text-xl font-bold mb-2">Conferences and Events</h3>
            <p class="mb-4">The conference team assists members of the academic community planning to host conferences to raise the profile of departments, the relevant associations, and the BERKELEY. The experienced professional conference organizing team remove the stress and strain of planning a conference. They can support you in various aspects of the event or the entire project and aim to ensure all details are arranged to your requirements and budget.</p>

            <h3 class="text-xl font-bold mb-2">Student Services</h3>
            <p class="mb-4">Student Services provides support to students and also to staff working with students. We are open Monday to Friday from 9am to 7pm during term time (9am to 5pm at other times).</p>
            <p class="mb-4">Student Services also offer an emergency out of hours service to students in halls of residence and private accommodation. Staff with concerns about a student’s welfare outside normal business hours can access assistance by calling the BERKELEY’s Security Team and asking for the Warden on duty.</p>
            <p class="mb-4">Student Services provides support in the following ways:</p>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>An information service for students</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Advice and support for students with disabilities</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Wellbeing, counseling, and mental health support</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>International and immigration advising</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Advice on money and finances</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Advice on academic issues</p>
                </li>
            </ul>

            <h3 class="text-xl font-bold mb-2">Policy Statement</h3>
            <p class="mb-4">BERKELEY is committed to providing high-quality service and to maintaining the highest standards for its learners, and other stakeholders. Recognition of prior learning and transfer of credit exists to enable learners to avoid duplication of learning and assessment. This policy explains what is defined as the recognition of prior learning and credit transfer, and BERKELEY’s policies for applying these.</p>

            <h3 class="text-xl font-bold mb-2">Policy Relevance</h3>
            <p class="mb-4">This policy defines recognition of prior learning and credit transfer, the circumstances in which they may be applicable to learners, and the policies for applying them where relevant.</p>

            <h3 class="text-xl font-bold mb-2">Policy Responsibility and Review</h3>
            <p class="mb-4">This policy is the responsibility of the Quality Manager and will be reviewed regularly.</p>

            <h3 class="text-xl font-bold mb-2">Recognition of Prior Learning</h3>
            <p class="mb-4">Recognition of prior learning (RPL) allows for a learner to be recognized for certain learning they have previously undertaken which has not been certificated or accredited through the RQF (Regulated Qualifications Framework) or QCF (Qualifications and Credit Framework), towards the regulated/unregulated qualification being studied.</p>
            <p class="mb-4">If approved for RPL, the learner does not need to attend learning for the approved unit(s), but must still undertake the assessment in full in order to achieve the assessment criteria.</p>
            <p class="mb-4">The learner must provide evidence of prior work, learning, and/or achievement, for consideration. This must be fully evidenced, valid, and reliable, and mapped to the learning outcomes and assessment criteria of all units for which the RPL claim is being made.</p>
            <p class="mb-4">RPL may be claimed against a whole unit or several units. It is not possible to claim part units.</p>
            <p class="mb-4">Under some circumstances, there may be a limit to the proportion of the qualification that can be achieved by RPL, and/or specific rules may apply to RPL eligibility of certain units. Full details of any requirements will be identified in the rules of combination for any regulated/unregulated qualifications offered by BERKELEY.</p>
            <p class="mb-4">BERKELEY remains the role of the assessor and internal quality assurance staff to ensure that assessment criteria are only deemed to have been met where assessment is valid, reliable and fit for purpose, and where evidence is adequate, sufficient, and authentic. The process of RPL is subject to the same standard of scrutiny through the application of existing quality assurance and monitoring processes as any other form of assessment. RPL assessments should be included in standardization and evaluation activities so that processes are reviewed, as with all other assessments.</p>
            <p class="mb-4">Where RPL is used towards a learner’s achievement of a regulated/unregulated qualification, a learner will still be charged the full fee for the qualification being claimed, as the full qualification is still subject to full external quality assurance of the assessment, by BERKELEY.</p>

            <h3 class="text-xl font-bold mb-2">Credit Transfer</h3>
            <p class="mb-4">Credit transfer allows for previously accredited achievement to count towards another regulated/unregulated qualification, where it is either allowed as an equivalency within the new qualification, or where the learner has already achieved one or more exact unit(s) belonging to that qualification.</p>
            <p class="mb-4">In the case of approved credit transfer, the learner does not need to repeat their assessment.</p>
            <p class="mb-4">The learner must provide evidence of certification for the unit(s) to the BERKELEY (the certificate itself is not required where a previous credit achievement was from BERKELEY).</p>
            <p class="mb-4">Credit transfer may be claimed against a whole unit or several units. It is not possible to claim part units.</p>

            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Provide evidence of units achieved, usually by way of the original certificate (not required where previous credit achievement was from BERKELEY).</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Make an application for credit transfer to BERKELEY.</p>
                </li>
            </ul>

            <p class="mb-4">Where credit transfer is used towards a learner’s achievement of a regulated/unregulated qualification, a learner will still be charged the full fee for the qualification being claimed which includes checking eligibility for credit transfer and authentication of the certificate(s).</p>


            <h3 class="text-xl font-bold mb-2">1. Purpose and Objectives</h3>
            <p class="mb-4">
                This policy sets out the BERKELEY’s commitment to the development of its staff, the broad range of development activities available, the responsibilities of the parties involved, and the funding arrangements for staff development activities.
            </p>

            <h3 class="text-xl font-bold mb-2">2. Definitions, Terms, Acronyms</h3>
            <p class="mb-4">No entries for this document.</p>

            <h3 class="text-xl font-bold mb-2">3. Policy Scope/Coverage</h3>
            <p class="mb-4">This policy applies to all BERKELEY staff.</p>

            <h3 class="text-xl font-bold mb-2">4. Policy Statement</h3>
            <p class="mb-4">
                The BERKELEY is dedicated to the pursuit of learning, but is itself required to be a “learning organisation”, so that it can continually improve the provision of learning opportunities to its students. Staff development is a vital investment from which our staff and the BERKELEY itself will benefit.
            </p>
            <p class="mb-4">
                This policy and development activities offered under it are designed to:
            </p>

            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Provide development opportunities essential for staff in induction, upgrading skills required for their current and future positions, attaining required competencies, and in personal development related to job performance.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Enhance the standard of performance of all staff in their current jobs.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Maintain and increase job satisfaction.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Provide support for career advancement, so that the BERKELEY will retain staff who perform well and staff are prepared for possible future responsibilities.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Improve and develop the ability of staff to initiate and respond constructively to change.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Maintain and improve organisational effectiveness and efficiency.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Support the BERKELEY’s principles of equity.</p>
                </li>
            </ul>

            <h3 class="text-xl font-bold mb-2">5. Types of Development Opportunities</h3>
            <p class="mb-4">
                The BERKELEY encourages staff to develop their capability through a broad range of activities, including (but not limited to) the following:
            </p>

            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Attending formal training courses and seminars (such as the Staff Development Program of courses).</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>On-the-job training.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Participation in mentoring, as a mentor or a mentee.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Participation in action learning programs.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Lateral transfers and job rotation.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Staff interchanges or secondments.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Internships.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Conferences.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Special studies program.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Study visits.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Multi-skilling.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Temporary performance of higher duties.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Support from a coach.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Undertaking professional development programs.</p>
                </li>
            </ul>

            <h3 class="text-xl font-bold mb-2">6. Monitoring and Reviewing this Policy</h3>
            <p class="mb-4">
                This policy will be reviewed every two years to ensure that it is aligned with changing staff development needs and industry standards. Regular feedback from staff will be taken into consideration.
            </p>

            <h3 class="text-xl font-bold mb-2">Development</h3>
            <p class="mb-4">
                Development is responsible for the BERKELEY’s developing strong relationships with alumni and friends throughout the world.
            </p>

            <h3 class="text-xl font-bold mb-2">General Information</h3>

            <h4 class="text-lg font-semibold mb-2">Mail Room:</h4>
            <p class="mb-4">
                Information on opening times and collections/deliveries can be found at the operations department.
            </p>

            <h4 class="text-lg font-semibold mb-2">Repairs:</h4>
            <p class="mb-4">
                For information on how to request a building maintenance repair, please call the administration department.
            </p>

            <h3 class="text-xl font-bold mb-2">Reasonable Adjustments Procedure</h3>
            <ul class="mt-2 space-y-0 font-medium">
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Reasonable adjustments in respect of marking and the identification of work from students with disabilities affecting written expression.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>Alternative forms of assessments and standardised adjustments in written examinations.</p>
                </li>
                <li class="flex lg:col-span-1 items-center gap-2">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                    </div>
                    <p>A number of reasonable adjustments to examinations are already permitted as standard on the basis of a needs assessment being carried out by the BERKELEY and supporting evidence being provided. These are:</p>
                </li>
            </ul>

            <ul class="ml-8 list-decimal">
                <li>Extra time (up to 30 minutes per hour)</li>
                <li>Rest breaks (up to 10 minutes per hour)</li>
                <li>Use of a scribe and/or reader</li>
                <li>Use of a computer</li>
                <li>Exam scripts to be flagged to marker</li>
                <li>Alternative format for exam papers</li>
                <li>Use of own equipment</li>
                <li>Provision of an adjustable chair and/or desk/footrest/writing slope.</li>
            </ul>

            <p class="mb-4">
                Where the mode of assessment puts a disabled student at a substantial disadvantage when compared with students who do not have that disability and the standardised adjustments to examinations are not effective in preventing the disadvantage, BERKELEY will consider other alternative modes of assessment, where it does not impact on the competency standards.
            </p>

            <p class="mb-4">
                Requests for consideration of an alternative mode of assessment will be for exceptional cases and will be considered on a case-by-case basis by the BERKELEY, who will liaise to assess what is possible and does not compromise competency standards.
            </p>

            <p class="mb-4">
                In deciding on the appropriateness of an alternative mode of assessment, the School will need to be guided by the competence standards for the programme. The programme specification should provide the basis for determining them.
            </p>

            <p class="mb-4">
                It may not always be possible to provide an alternative mode of assessment, for example, where the mode of examination and the competence standard are inextricably linked. However, where a request is declined, the reasons for this decision will be stated with reference to the key competencies of the programme.
            </p>


        </div>

    </main>

    </div>
</section>

@endsection

@push('script')
    <script>
        // Smooth scroll to anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();

                const offsetTop = document.querySelector(this.getAttribute('href')).offsetTop;
                window.scrollTo({
                    top: offsetTop - 80, // Adjusted scroll position
                    behavior: 'smooth'
                });
            });
        });
    </script>
@endpush