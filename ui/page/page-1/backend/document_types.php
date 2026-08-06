<?php
// document_types.php
return [
    'resume'                => ['label' => 'Resume', 'required' => true, 'multiple' => false],
    'valid_id'              => ['label' => 'Valid ID(s)', 'required' => true, 'multiple' => true, 'max' => 3],
    'psa'                   => ['label' => 'PSA Birth Certificate', 'required' => true, 'multiple' => false],
    'diploma_tor'           => ['label' => 'Diploma / TOR', 'required' => true, 'multiple' => false],
    'sss'                   => ['label' => 'SSS', 'required' => true, 'multiple' => false],
    'pagibig'               => ['label' => 'Pag-IBIG', 'required' => true, 'multiple' => false],
    'philhealth'            => ['label' => 'PhilHealth', 'required' => true, 'multiple' => false],
    'bir_tin'               => ['label' => 'BIR TIN', 'required' => true, 'multiple' => false],
    'medical_certificate'   => ['label' => 'Medical Certificate / Result', 'required' => true, 'multiple' => false],
    'drug_test'             => ['label' => 'Drug Test', 'required' => true, 'multiple' => false],
    'urinalysis'            => ['label' => 'Urinalysis', 'required' => true, 'multiple' => false],
    'fecalysis'             => ['label' => 'Fecalysis', 'required' => true, 'multiple' => false],
    'cbc'                   => ['label' => 'CBC', 'required' => true, 'multiple' => false],
    'xray'                  => ['label' => 'X-Ray', 'required' => true, 'multiple' => false],
    'nbi_police_clearance'  => ['label' => 'NBI / Police Clearance', 'required' => true, 'multiple' => false],
    'coe'                   => ['label' => 'COE (Latest, if applicable)', 'required' => false, 'multiple' => false],
    'marriage_certificate'  => ['label' => 'Marriage Certificate (if married)', 'required' => false, 'multiple' => false],
];