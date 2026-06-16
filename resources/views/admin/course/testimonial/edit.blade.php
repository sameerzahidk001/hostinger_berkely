@extends('admin.layout.app')
@section('title', 'Testimonial')
@push('style')
<style>
    .mb{
        margin-bottom: 1.5rem;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Testimonial</h2>
        <ol class="breadcrumb">
             <li><a href="{{route('admin.home')}}">Home</a></li>
            <li><a href="{{route('testimonial.index')}}">Testimonial</a></li>
            <li class="active"><strong>Create Testimonial</strong></li>
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>
<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Edit Testimonial of {{ $testimonial->course->title }}</h5>
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

                    <form role="form" action="{{ route('testimonial.update', $testimonial->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="course_id" value="{{ $testimonial->course->id }}">
                        <div class="row">
                            <div class="col-lg-6 mb">
                                <label for="">Alumni Name</label>
                                <input class="form-control" placeholder="Add Alumni Name" type="text" name="name" value="{{ old('name',  $testimonial->name) }}">
                            </div>
                            
                            <div class="col-lg-6 mb">
                                <label class="mb-1">Image</label>
                                <input class="form-control" type="file" name="image">
                                <a href="{{ asset($testimonial->image) }}" target="_blank">
                                    Show Image
                                </a>
                            </div>

                            <div class="col-lg-6 mb">
                                <label for="">Alumni City</label>
                                <input class="form-control" placeholder="Add Alumni City" type="text" name="city" value="{{ old('city',  $testimonial->city) }}">
                            </div>

                            <div class="col-lg-6 mb">
                                <label class="mb-1">Country</label>
                                <select name="country" id="" class="form-control">
                                    <option value="">-- Select Country --</option>
                                    <option value="Afghanistan" {{ $testimonial->country == 'Afghanistan' ? 'selected' : '' }}>Afghanistan</option>
                                    <option value="Albania" {{ $testimonial->country == 'Albania' ? 'selected' : '' }}>Albania</option>
                                    <option value="Algeria" {{ $testimonial->country == 'Algeria' ? 'selected' : '' }}>Algeria</option>
                                    <option value="Andorra" {{ $testimonial->country == 'Andorra' ? 'selected' : '' }}>Andorra</option>
                                    <option value="Angola" {{ $testimonial->country == 'Angola' ? 'selected' : '' }}>Angola</option>
                                    <option value="Antigua and Barbuda" {{ $testimonial->country == 'Antigua and Barbuda' ? 'selected' : '' }}>Antigua and Barbuda</option>
                                    <option value="Argentina" {{ $testimonial->country == 'Argentina' ? 'selected' : '' }}>Argentina</option>
                                    <option value="Armenia" {{ $testimonial->country == 'Armenia' ? 'selected' : '' }}>Armenia</option>
                                    <option value="Australia" {{ $testimonial->country == 'Australia' ? 'selected' : '' }}>Australia</option>
                                    <option value="Austria" {{ $testimonial->country == 'Austria' ? 'selected' : '' }}>Austria</option>
                                    <option value="Azerbaijan" {{ $testimonial->country == 'Azerbaijan' ? 'selected' : '' }}>Azerbaijan</option>
                                    <option value="Bahamas" {{ $testimonial->country == 'Bahamas' ? 'selected' : '' }}>Bahamas</option>
                                    <option value="Bahrain" {{ $testimonial->country == 'Bahrain' ? 'selected' : '' }}>Bahrain</option>
                                    <option value="Bangladesh" {{ $testimonial->country == 'Bangladesh' ? 'selected' : '' }}>Bangladesh</option>
                                    <option value="Barbados" {{ $testimonial->country == 'Barbados' ? 'selected' : '' }}>Barbados</option>
                                    <option value="Belarus" {{ $testimonial->country == 'Belarus' ? 'selected' : '' }}>Belarus</option>
                                    <option value="Belgium" {{ $testimonial->country == 'Belgium' ? 'selected' : '' }}>Belgium</option>
                                    <option value="Belize" {{ $testimonial->country == 'Belize' ? 'selected' : '' }}>Belize</option>
                                    <option value="Benin" {{ $testimonial->country == 'Benin' ? 'selected' : '' }}>Benin</option>
                                    <option value="Bhutan" {{ $testimonial->country == 'Bhutan' ? 'selected' : '' }}>Bhutan</option>
                                    <option value="Bolivia" {{ $testimonial->country == 'Bolivia' ? 'selected' : '' }}>Bolivia</option>
                                    <option value="Bosnia and Herzegovina" {{ $testimonial->country == 'Bosnia and Herzegovina' ? 'selected' : '' }}>Bosnia and Herzegovina</option>
                                    <option value="Botswana" {{ $testimonial->country == 'Botswana' ? 'selected' : '' }}>Botswana</option>
                                    <option value="Brazil" {{ $testimonial->country == 'Brazil' ? 'selected' : '' }}>Brazil</option>
                                    <option value="Brunei" {{ $testimonial->country == 'Brunei' ? 'selected' : '' }}>Brunei</option>
                                    <option value="Bulgaria" {{ $testimonial->country == 'Bulgaria' ? 'selected' : '' }}>Bulgaria</option>
                                    <option value="Burkina Faso" {{ $testimonial->country == 'Burkina Faso' ? 'selected' : '' }}>Burkina Faso</option>
                                    <option value="Burundi" {{ $testimonial->country == 'Burundi' ? 'selected' : '' }}>Burundi</option>
                                    <option value="Cabo Verde" {{ $testimonial->country == 'Cabo Verde' ? 'selected' : '' }}>Cabo Verde</option>
                                    <option value="Cambodia" {{ $testimonial->country == 'Cambodia' ? 'selected' : '' }}>Cambodia</option>
                                    <option value="Cameroon" {{ $testimonial->country == 'Cameroon' ? 'selected' : '' }}>Cameroon</option>
                                    <option value="Canada" {{ $testimonial->country == 'Canada' ? 'selected' : '' }}>Canada</option>
                                    <option value="Central African Republic" {{ $testimonial->country == 'Central African Republic' ? 'selected' : '' }}>Central African Republic</option>
                                    <option value="Chad" {{ $testimonial->country == 'Chad' ? 'selected' : '' }}>Chad</option>
                                    <option value="Chile" {{ $testimonial->country == 'Chile' ? 'selected' : '' }}>Chile</option>
                                    <option value="China" {{ $testimonial->country == 'China' ? 'selected' : '' }}>China</option>
                                    <option value="Colombia" {{ $testimonial->country == 'Colombia' ? 'selected' : '' }}>Colombia</option>
                                    <option value="Comoros" {{ $testimonial->country == 'Comoros' ? 'selected' : '' }}>Comoros</option>
                                    <option value="Congo, Democratic Republic of the" {{ $testimonial->country == 'Congo, Democratic Republic of the' ? 'selected' : '' }}>Congo, Democratic Republic of the</option>
                                    <option value="Congo, Republic of the" {{ $testimonial->country == 'Congo, Republic of the' ? 'selected' : '' }}>Congo, Republic of the</option>
                                    <option value="Costa Rica" {{ $testimonial->country == 'Costa Rica' ? 'selected' : '' }}>Costa Rica</option>
                                    <option value="Croatia" {{ $testimonial->country == 'Croatia' ? 'selected' : '' }}>Croatia</option>
                                    <option value="Cuba" {{ $testimonial->country == 'Cuba' ? 'selected' : '' }}>Cuba</option>
                                    <option value="Cyprus" {{ $testimonial->country == 'Cyprus' ? 'selected' : '' }}>Cyprus</option>
                                    <option value="Czech Republic" {{ $testimonial->country == 'Czech Republic' ? 'selected' : '' }}>Czech Republic</option>
                                    <option value="Denmark" {{ $testimonial->country == 'Denmark' ? 'selected' : '' }}>Denmark</option>
                                    <option value="Djibouti" {{ $testimonial->country == 'Djibouti' ? 'selected' : '' }}>Djibouti</option>
                                    <option value="Dominica" {{ $testimonial->country == 'Dominica' ? 'selected' : '' }}>Dominica</option>
                                    <option value="Dominican Republic" {{ $testimonial->country == 'Dominican Republic' ? 'selected' : '' }}>Dominican Republic</option>
                                    <option value="Ecuador" {{ $testimonial->country == 'Ecuador' ? 'selected' : '' }}>Ecuador</option>
                                    <option value="Egypt" {{ $testimonial->country == 'Egypt' ? 'selected' : '' }}>Egypt</option>
                                    <option value="El Salvador" {{ $testimonial->country == 'El Salvador' ? 'selected' : '' }}>El Salvador</option>
                                    <option value="Equatorial Guinea" {{ $testimonial->country == 'Equatorial Guinea' ? 'selected' : '' }}>Equatorial Guinea</option>
                                    <option value="Eritrea" {{ $testimonial->country == 'Eritrea' ? 'selected' : '' }}>Eritrea</option>
                                    <option value="Estonia" {{ $testimonial->country == 'Estonia' ? 'selected' : '' }}>Estonia</option>
                                    <option value="Eswatini" {{ $testimonial->country == 'Eswatini' ? 'selected' : '' }}>Eswatini</option>
                                    <option value="Ethiopia" {{ $testimonial->country == 'Ethiopia' ? 'selected' : '' }}>Ethiopia</option>
                                    <option value="Fiji" {{ $testimonial->country == 'Fiji' ? 'selected' : '' }}>Fiji</option>
                                    <option value="Finland" {{ $testimonial->country == 'Finland' ? 'selected' : '' }}>Finland</option>
                                    <option value="France" {{ $testimonial->country == 'France' ? 'selected' : '' }}>France</option>
                                    <option value="Gabon" {{ $testimonial->country == 'Gabon' ? 'selected' : '' }}>Gabon</option>
                                    <option value="Gambia" {{ $testimonial->country == 'Gambia' ? 'selected' : '' }}>Gambia</option>
                                    <option value="Georgia" {{ $testimonial->country == 'Georgia' ? 'selected' : '' }}>Georgia</option>
                                    <option value="Germany" {{ $testimonial->country == 'Germany' ? 'selected' : '' }}>Germany</option>
                                    <option value="Ghana" {{ $testimonial->country == 'Ghana' ? 'selected' : '' }}>Ghana</option>
                                    <option value="Greece" {{ $testimonial->country == 'Greece' ? 'selected' : '' }}>Greece</option>
                                    <option value="Grenada" {{ $testimonial->country == 'Grenada' ? 'selected' : '' }}>Grenada</option>
                                    <option value="Guatemala" {{ $testimonial->country == 'Guatemala' ? 'selected' : '' }}>Guatemala</option>
                                    <option value="Guinea" {{ $testimonial->country == 'Guinea' ? 'selected' : '' }}>Guinea</option>
                                    <option value="Guinea-Bissau" {{ $testimonial->country == 'Guinea-Bissau' ? 'selected' : '' }}>Guinea-Bissau</option>
                                    <option value="Guyana" {{ $testimonial->country == 'Guyana' ? 'selected' : '' }}>Guyana</option>
                                    <option value="Haiti" {{ $testimonial->country == 'Haiti' ? 'selected' : '' }}>Haiti</option>
                                    <option value="Honduras" {{ $testimonial->country == 'Honduras' ? 'selected' : '' }}>Honduras</option>
                                    <option value="Hungary" {{ $testimonial->country == 'Hungary' ? 'selected' : '' }}>Hungary</option>
                                    <option value="Iceland" {{ $testimonial->country == 'Iceland' ? 'selected' : '' }}>Iceland</option>
                                    <option value="India" {{ $testimonial->country == 'India' ? 'selected' : '' }}>India</option>
                                    <option value="Indonesia" {{ $testimonial->country == 'Indonesia' ? 'selected' : '' }}>Indonesia</option>
                                    <option value="Iran" {{ $testimonial->country == 'Iran' ? 'selected' : '' }}>Iran</option>
                                    <option value="Iraq" {{ $testimonial->country == 'Iraq' ? 'selected' : '' }}>Iraq</option>
                                    <option value="Ireland" {{ $testimonial->country == 'Ireland' ? 'selected' : '' }}>Ireland</option>
                                    <option value="Israel" {{ $testimonial->country == 'Israel' ? 'selected' : '' }}>Israel</option>
                                    <option value="Italy" {{ $testimonial->country == 'Italy' ? 'selected' : '' }}>Italy</option>
                                    <option value="Jamaica" {{ $testimonial->country == 'Jamaica' ? 'selected' : '' }}>Jamaica</option>
                                    <option value="Japan" {{ $testimonial->country == 'Japan' ? 'selected' : '' }}>Japan</option>
                                    <option value="Jordan" {{ $testimonial->country == 'Jordan' ? 'selected' : '' }}>Jordan</option>
                                    <option value="Kazakhstan" {{ $testimonial->country == 'Kazakhstan' ? 'selected' : '' }}>Kazakhstan</option>
                                    <option value="Kenya" {{ $testimonial->country == 'Kenya' ? 'selected' : '' }}>Kenya</option>
                                    <option value="Kiribati" {{ $testimonial->country == 'Kiribati' ? 'selected' : '' }}>Kiribati</option>
                                    <option value="Korea, North" {{ $testimonial->country == 'Korea, North' ? 'selected' : '' }}>Korea, North</option>
                                    <option value="Korea, South" {{ $testimonial->country == 'Korea, South' ? 'selected' : '' }}>Korea, South</option>
                                    <option value="Kosovo" {{ $testimonial->country == 'Kosovo' ? 'selected' : '' }}>Kosovo</option>
                                    <option value="Kuwait" {{ $testimonial->country == 'Kuwait' ? 'selected' : '' }}>Kuwait</option>
                                    <option value="Kyrgyzstan" {{ $testimonial->country == 'Kyrgyzstan' ? 'selected' : '' }}>Kyrgyzstan</option>
                                    <option value="Laos" {{ $testimonial->country == 'Laos' ? 'selected' : '' }}>Laos</option>
                                    <option value="Latvia" {{ $testimonial->country == 'Latvia' ? 'selected' : '' }}>Latvia</option>
                                    <option value="Lebanon" {{ $testimonial->country == 'Lebanon' ? 'selected' : '' }}>Lebanon</option>
                                    <option value="Lesotho" {{ $testimonial->country == 'Lesotho' ? 'selected' : '' }}>Lesotho</option>
                                    <option value="Liberia" {{ $testimonial->country == 'Liberia' ? 'selected' : '' }}>Liberia</option>
                                    <option value="Libya" {{ $testimonial->country == 'Libya' ? 'selected' : '' }}>Libya</option>
                                    <option value="Liechtenstein" {{ $testimonial->country == 'Liechtenstein' ? 'selected' : '' }}>Liechtenstein</option>
                                    <option value="Lithuania" {{ $testimonial->country == 'Lithuania' ? 'selected' : '' }}>Lithuania</option>
                                    <option value="Luxembourg" {{ $testimonial->country == 'Luxembourg' ? 'selected' : '' }}>Luxembourg</option>
                                    <option value="Madagascar" {{ $testimonial->country == 'Madagascar' ? 'selected' : '' }}>Madagascar</option>
                                    <option value="Malawi" {{ $testimonial->country == 'Malawi' ? 'selected' : '' }}>Malawi</option>
                                    <option value="Malaysia" {{ $testimonial->country == 'Malaysia' ? 'selected' : '' }}>Malaysia</option>
                                    <option value="Maldives" {{ $testimonial->country == 'Maldives' ? 'selected' : '' }}>Maldives</option>
                                    <option value="Mali" {{ $testimonial->country == 'Mali' ? 'selected' : '' }}>Mali</option>
                                    <option value="Malta" {{ $testimonial->country == 'Malta' ? 'selected' : '' }}>Malta</option>
                                    <option value="Marshall Islands" {{ $testimonial->country == 'Marshall Islands' ? 'selected' : '' }}>Marshall Islands</option>
                                    <option value="Mauritania" {{ $testimonial->country == 'Mauritania' ? 'selected' : '' }}>Mauritania</option>
                                    <option value="Mauritius" {{ $testimonial->country == 'Mauritius' ? 'selected' : '' }}>Mauritius</option>
                                    <option value="Mexico" {{ $testimonial->country == 'Mexico' ? 'selected' : '' }}>Mexico</option>
                                    <option value="Micronesia" {{ $testimonial->country == 'Micronesia' ? 'selected' : '' }}>Micronesia</option>
                                    <option value="Moldova" {{ $testimonial->country == 'Moldova' ? 'selected' : '' }}>Moldova</option>
                                    <option value="Monaco" {{ $testimonial->country == 'Monaco' ? 'selected' : '' }}>Monaco</option>
                                    <option value="Mongolia" {{ $testimonial->country == 'Mongolia' ? 'selected' : '' }}>Mongolia</option>
                                    <option value="Montenegro" {{ $testimonial->country == 'Montenegro' ? 'selected' : '' }}>Montenegro</option>
                                    <option value="Morocco" {{ $testimonial->country == 'Morocco' ? 'selected' : '' }}>Morocco</option>
                                    <option value="Mozambique" {{ $testimonial->country == 'Mozambique' ? 'selected' : '' }}>Mozambique</option>
                                    <option value="Myanmar" {{ $testimonial->country == 'Myanmar' ? 'selected' : '' }}>Myanmar</option>
                                    <option value="Namibia" {{ $testimonial->country == 'Namibia' ? 'selected' : '' }}>Namibia</option>
                                    <option value="Nauru" {{ $testimonial->country == 'Nauru' ? 'selected' : '' }}>Nauru</option>
                                    <option value="Nepal" {{ $testimonial->country == 'Nepal' ? 'selected' : '' }}>Nepal</option>
                                    <option value="Netherlands" {{ $testimonial->country == 'Netherlands' ? 'selected' : '' }}>Netherlands</option>
                                    <option value="New Zealand" {{ $testimonial->country == 'New Zealand' ? 'selected' : '' }}>New Zealand</option>
                                    <option value="Nicaragua" {{ $testimonial->country == 'Nicaragua' ? 'selected' : '' }}>Nicaragua</option>
                                    <option value="Niger" {{ $testimonial->country == 'Niger' ? 'selected' : '' }}>Niger</option>
                                    <option value="Nigeria" {{ $testimonial->country == 'Nigeria' ? 'selected' : '' }}>Nigeria</option>
                                    <option value="North Macedonia" {{ $testimonial->country == 'North Macedonia' ? 'selected' : '' }}>North Macedonia</option>
                                    <option value="Norway" {{ $testimonial->country == 'Norway' ? 'selected' : '' }}>Norway</option>
                                    <option value="Oman" {{ $testimonial->country == 'Oman' ? 'selected' : '' }}>Oman</option>
                                    <option value="Pakistan" {{ $testimonial->country == 'Pakistan' ? 'selected' : '' }}>Pakistan</option>
                                    <option value="Palau" {{ $testimonial->country == 'Palau' ? 'selected' : '' }}>Palau</option>
                                    <option value="Panama" {{ $testimonial->country == 'Panama' ? 'selected' : '' }}>Panama</option>
                                    <option value="Papua New Guinea" {{ $testimonial->country == 'Papua New Guinea' ? 'selected' : '' }}>Papua New Guinea</option>
                                    <option value="Paraguay" {{ $testimonial->country == 'Paraguay' ? 'selected' : '' }}>Paraguay</option>
                                    <option value="Peru" {{ $testimonial->country == 'Peru' ? 'selected' : '' }}>Peru</option>
                                    <option value="Philippines" {{ $testimonial->country == 'Philippines' ? 'selected' : '' }}>Philippines</option>
                                    <option value="Poland" {{ $testimonial->country == 'Poland' ? 'selected' : '' }}>Poland</option>
                                    <option value="Portugal" {{ $testimonial->country == 'Portugal' ? 'selected' : '' }}>Portugal</option>
                                    <option value="Qatar" {{ $testimonial->country == 'Qatar' ? 'selected' : '' }}>Qatar</option>
                                    <option value="Romania" {{ $testimonial->country == 'Romania' ? 'selected' : '' }}>Romania</option>
                                    <option value="Russia" {{ $testimonial->country == 'Russia' ? 'selected' : '' }}>Russia</option>
                                    <option value="Rwanda" {{ $testimonial->country == 'Rwanda' ? 'selected' : '' }}>Rwanda</option>
                                    <option value="Saint Kitts and Nevis" {{ $testimonial->country == 'Saint Kitts and Nevis' ? 'selected' : '' }}>Saint Kitts and Nevis</option>
                                    <option value="Saint Lucia" {{ $testimonial->country == 'Saint Lucia' ? 'selected' : '' }}>Saint Lucia</option>
                                    <option value="Saint Vincent and the Grenadines" {{ $testimonial->country == 'Saint Vincent and the Grenadines' ? 'selected' : '' }}>Saint Vincent and the Grenadines</option>
                                    <option value="Samoa" {{ $testimonial->country == 'Samoa' ? 'selected' : '' }}>Samoa</option>
                                    <option value="San Marino" {{ $testimonial->country == 'San Marino' ? 'selected' : '' }}>San Marino</option>
                                    <option value="Sao Tome and Principe" {{ $testimonial->country == 'Sao Tome and Principe' ? 'selected' : '' }}>Sao Tome and Principe</option>
                                    <option value="Saudi Arabia" {{ $testimonial->country == 'Saudi Arabia' ? 'selected' : '' }}>Saudi Arabia</option>
                                    <option value="Senegal" {{ $testimonial->country == 'Senegal' ? 'selected' : '' }}>Senegal</option>
                                    <option value="Serbia" {{ $testimonial->country == 'Serbia' ? 'selected' : '' }}>Serbia</option>
                                    <option value="Seychelles" {{ $testimonial->country == 'Seychelles' ? 'selected' : '' }}>Seychelles</option>
                                    <option value="Sierra Leone" {{ $testimonial->country == 'Sierra Leone' ? 'selected' : '' }}>Sierra Leone</option>
                                    <option value="Singapore" {{ $testimonial->country == 'Singapore' ? 'selected' : '' }}>Singapore</option>
                                    <option value="Slovakia" {{ $testimonial->country == 'Slovakia' ? 'selected' : '' }}>Slovakia</option>
                                    <option value="Slovenia" {{ $testimonial->country == 'Slovenia' ? 'selected' : '' }}>Slovenia</option>
                                    <option value="Solomon Islands" {{ $testimonial->country == 'Solomon Islands' ? 'selected' : '' }}>Solomon Islands</option>
                                    <option value="Somalia" {{ $testimonial->country == 'Somalia' ? 'selected' : '' }}>Somalia</option>
                                    <option value="South Africa" {{ $testimonial->country == 'South Africa' ? 'selected' : '' }}>South Africa</option>
                                    <option value="Spain" {{ $testimonial->country == 'Spain' ? 'selected' : '' }}>Spain</option>
                                    <option value="Sri Lanka" {{ $testimonial->country == 'Sri Lanka' ? 'selected' : '' }}>Sri Lanka</option>
                                    <option value="Sudan" {{ $testimonial->country == 'Sudan' ? 'selected' : '' }}>Sudan</option>
                                    <option value="Suriname" {{ $testimonial->country == 'Suriname' ? 'selected' : '' }}>Suriname</option>
                                    <option value="Sweden" {{ $testimonial->country == 'Sweden' ? 'selected' : '' }}>Sweden</option>
                                    <option value="Switzerland" {{ $testimonial->country == 'Switzerland' ? 'selected' : '' }}>Switzerland</option>
                                    <option value="Syria" {{ $testimonial->country == 'Syria' ? 'selected' : '' }}>Syria</option>
                                    <option value="Taiwan" {{ $testimonial->country == 'Taiwan' ? 'selected' : '' }}>Taiwan</option>
                                    <option value="Tajikistan" {{ $testimonial->country == 'Tajikistan' ? 'selected' : '' }}>Tajikistan</option>
                                    <option value="Tanzania" {{ $testimonial->country == 'Tanzania' ? 'selected' : '' }}>Tanzania</option>
                                    <option value="Thailand" {{ $testimonial->country == 'Thailand' ? 'selected' : '' }}>Thailand</option>
                                    <option value="Togo" {{ $testimonial->country == 'Togo' ? 'selected' : '' }}>Togo</option>
                                    <option value="Tonga" {{ $testimonial->country == 'Tonga' ? 'selected' : '' }}>Tonga</option>
                                    <option value="Trinidad and Tobago" {{ $testimonial->country == 'Trinidad and Tobago' ? 'selected' : '' }}>Trinidad and Tobago</option>
                                    <option value="Tunisia" {{ $testimonial->country == 'Tunisia' ? 'selected' : '' }}>Tunisia</option>
                                    <option value="Turkey" {{ $testimonial->country == 'Turkey' ? 'selected' : '' }}>Turkey</option>
                                    <option value="Turkmenistan" {{ $testimonial->country == 'Turkmenistan' ? 'selected' : '' }}>Turkmenistan</option>
                                    <option value="Tuvalu" {{ $testimonial->country == 'Tuvalu' ? 'selected' : '' }}>Tuvalu</option>
                                    <option value="Uganda" {{ $testimonial->country == 'Uganda' ? 'selected' : '' }}>Uganda</option>
                                    <option value="Ukraine" {{ $testimonial->country == 'Ukraine' ? 'selected' : '' }}>Ukraine</option>
                                    <option value="United Arab Emirates" {{ $testimonial->country == 'United Arab Emirates' ? 'selected' : '' }}>United Arab Emirates</option>
                                    <option value="United Kingdom" {{ $testimonial->country == 'United Kingdom' ? 'selected' : '' }}>United Kingdom</option>
                                    <option value="United States" {{ $testimonial->country == 'United States' ? 'selected' : '' }}>United States</option>
                                    <option value="Uruguay" {{ $testimonial->country == 'Uruguay' ? 'selected' : '' }}>Uruguay</option>
                                    <option value="Uzbekistan" {{ $testimonial->country == 'Uzbekistan' ? 'selected' : '' }}>Uzbekistan</option>
                                    <option value="Vanuatu" {{ $testimonial->country == 'Vanuatu' ? 'selected' : '' }}>Vanuatu</option>
                                    <option value="Vatican City" {{ $testimonial->country == 'Vatican City' ? 'selected' : '' }}>Vatican City</option>
                                    <option value="Venezuela" {{ $testimonial->country == 'Venezuela' ? 'selected' : '' }}>Venezuela</option>
                                    <option value="Vietnam" {{ $testimonial->country == 'Vietnam' ? 'selected' : '' }}>Vietnam</option>
                                    <option value="Yemen" {{ $testimonial->country == 'Yemen' ? 'selected' : '' }}>Yemen</option>
                                    <option value="Zambia" {{ $testimonial->country == 'Zambia' ? 'selected' : '' }}>Zambia</option>
                                    <option value="Zimbabwe" {{ $testimonial->country == 'Zimbabwe' ? 'selected' : '' }}>Zimbabwe</option>
                                </select>
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Date</label>
<input class="form-control" type="date" name="date" 
       value="{{ old('date', $testimonial->date ? \Carbon\Carbon::parse($testimonial->date)->format('Y-m-d') : '') }}">                            </div>
                            <div class="col-lg-12 mb">
                                <label class="mb-1">Text</label>
                                <textarea name="text" class="form-control text-editor" value="{{ old('text',  $testimonial->text) }}"  rows="2">{{ $testimonial->text }}</textarea>
                            </div>
                            <div class="col-lg-12">
                                <hr>
                            </div>
                            <div class="col-lg-12 mb">
                                <label class="mb-1">Youtube Video URL</label>
                                <input type="url" name="asset_path" class="form-control" value="{{ old('asset_path',  $testimonial->asset_path) }}">
                                @if($testimonial->asset_path)
                                    <a href="{{ asset($testimonial->asset_path) }}" target="_blank">
                                        Show Video
                                    </a>
                            
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 0;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Submit</button>
                                <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')

<!-- <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('.text-editor').forEach((editorElement) => {
            ClassicEditor.create(editorElement)
                .catch(error => {
                    console.error(error);
                });
        });
    });
</script> -->
@endpush
