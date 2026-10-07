% medical.pl -- tiny medical expert system demo (SWI-Prolog)
% ---------- FACTS ----------
% patient_symptom(Patient, Symptom).
patient_symptom(ali, fever).
patient_symptom(ali, cough).
patient_symptom(ali, sore_throat).
patient_symptom(rita, headache).
patient_symptom(rita, nausea).
patient_symptom(rita, stiff_neck).
% symptom_severity(Symptom, Level). % optional extra info
symptom_severity(fever, high).
symptom_severity(cough, mild).
symptom_severity(headache, moderate).
% ---------- DISEASE DEFINITIONS (RULES) ----------
% disease(Disease, ListOfSymptoms) is declarative metadata (not used for reasoning here,
% but handy for explanation).
disease(flu, [fever, cough, sore_throat]).
disease(meningitis, [fever, headache, stiff_neck, nausea]).
disease(common_cold, [cough, sore_throat]).
% simple rule: a patient is suspected of a disease if they have ALL required symptoms
suspect(Patient, Disease) :-
 disease(Disease, Symptoms),
 has_all_symptoms(Patient, Symptoms).
% helper: check patient has every symptom in a list
has_all_symptoms(_, []).
has_all_symptoms(Patient, [S|Rest]) :-
 patient_symptom(Patient, S),
 has_all_symptoms(Patient, Rest).
% a weaker rule that suggests a disease if patient has at least N symptoms in the disease list
suspect_with_threshold(Patient, Disease, N) :-
 disease(Disease, Symptoms),
 count_matching_symptoms(Patient, Symptoms, Count),
 Count >= N.
% count matching symptoms
count_matching_symptoms(Patient, Symptoms, Count) :-
 include({Patient}/[S]>>patient_symptom(Patient,S), Symptoms, Matching),
 length(Matching, Count).
% ---------- EXPLANATION RULE ----------
explain(Patient, Disease) :-
 suspect(Patient, Disease),
 format('~w is suspected to have ~w because they show: ', [Patient, Disease]),
 disease(Disease, Symptoms),
 findall(S, (member(S, Symptoms), patient_symptom(Patient, S)), List),
 writeln(List).
