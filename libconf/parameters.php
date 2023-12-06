<?php

/**
 * Fichier global de paramètres utilisés 
 * dans le processus de segmentation
 */

$PARAMS_ASTRO = array(
// constantes Tracker
"TRACKER_GPS_PERIOD" => 1,         // periode du GPS
  
// constantes lib mobility
"MOBILITY_SEGMENT_DURATION" => 20, // longueur de la période de calcul de l'indice d'activité

// constantes traitement micro arret 
"MICROSTOP_MAX_SPEED" => 3,      // vitesse maximale dans micro arret
"MICROSTOP_MAX_DISTANCE" => 50,  // rayon maximal du micro arret
"MICROSTOP_MIN_DURATION" => 30,  // durée minimum du micro arret (en s.)
"MICROSTOP_MODALITY_LOOKUP_DURATION" => 180, // duree d'etude de la modalité de transport (vitesse) avant et après le micro-arret (en s.)
"MICROSTOP_MODALITY_MOTOR_FILTER_MIN_DURATION" => 50, // duree minimum d'un micro-arret en mode motorise (en s.)
"MICROSTOP_MODALITY_MOTOR_MIN_SPEED" => 15, // vitesse minimale de détection d'un déplacement motorisé avant et après micro-arret (en km/h)

"MICROSTOP_ATTACH_LIEU_RADIUS"      => 100,   // ecart max entre un lieu et un ma pour le rattachement (en m)
"MICROSTOP_ATTACH_LIEU_DURATION"    => 25*60, // duree max d'écart temporel (en s.) entre la fin d'un lieu/le début d'un ma ou la fin d'un ma/le debut d'un lieu pour le rattacher (25 minutes)
"MICROSTOP_CLUSTER_REC_RADIUS"      => 100,   // distance max de raccrochage des ma pour en faire des ma recurrents (en m) 

"MICROSTOP_MERGE_CLUSTER_TIME"      => 180,  // limite de duree maximale en s.   entre microarrets pour agregation 
"MICROSTOP_MERGE_CLUSTER_DISTANCE"  => 150,  // limite de distance maximale en m entre microarrets pour agregation 
"MICROSTOP_MAX_DURATION"            => 300,  // duree maximale micro-arret en s, au dela --> lieu
// traitement lieux
"CLUSTER_REFINE_LIEU_RADIUS"         => 50,   // rayon d'agrégation des lieux proches 



// constantes de filtrage micro arret
"MICROSTOP_FILTER_TIMESLOT_WEEK" => array(       // plage horaire de selection des microarret dans la semaine
  array("start" => "7:45", "end" => "8:30"),
  array("start" => "11:30", "end" => "14:00"),
  array("start" => "15:30", "end" => "20:00") ),
"MICROSTOP_FILTER_INDOOR" =>  3,       // nombre de satellites en deça duquel (<=) on considère la réception en indoor

// constantes de scoring micro arret
"MICROSTOP_SCORE_RECURRENCE_WEIGHT"       => 100.0, //
"MICROSTOP_SCORE_TIMESLOT_WEIGHT"         => 1000.0, //
"MICROSTOP_SCORE_POICOUNT_WEIGHT"         => 100.0, //
"MICROSTOP_SCORE_DURATION_WEIGHT"         => 1000.0, //
"MICROSTOP_SCORE_INDOOR_WEIGHT"           => -500.0, //
"MICROSTOP_SCORE_MOTIONLEVEL_WEIGHT"      => 1000.0, //
    
"MICROSTOP_SCORE_MOTIONLEVEL_THRESHOLD"   => 2.0 * 5000000.0, //
"MICROSTOP_SCORE_MAXCOUNT"                => 10, // nombre max de microarrets conservés pour la partie commentaire

// detection de mode de transport
"TRANSPORTMODE_DETECTION_ENABLED"         => true,
"TRANSPORTMODE_PIETON_DETECTION_CLASS"    => array(4,5),  // liste des classes de mobilité pouvant correspondre à de la mobilité pietonne
"TRANSPORTMODE_PIETON_MAX_VITESSE"        => 5,             // vitesse max acceptée pour la détection de mobilité piétonne (en kmPIETON/h)
"TRANSPORTMODE_PIETON_CLASS"              => 5,   // classe de transport finale pour la mobilité pietonne
"TRANSPORTMODE_PIETON_MIN_DURATION"       => 20,  // duree minimale d'un parcours pieton (en s.)

"TRANSPORTMODE_MOTOR_DETECTION_CLASS"       => array(1,2,3,4),     // liste des classes de mobilité pouvant correspondre à de la mobilité pietonne
"TRANSPORTMODE_MOTOR_MIN_DETECTION_VITESSE" => 20, // vitesse min de détection d'un deplacement motorise (en km/h)
"TRANSPORTMODE_MOTOR_CLASS"                 => 3,   // classe de transport finale pour la mobilité non pietonne/motorisée
"TRANSPORTMODE_MOTOR_MIN_DURATION"          => 60 * 2.0,  // duree minimale d'un parcours pieton 2 min.



"POI_MATCHING_RADIUS" => 100.0,    // rayon de raccorchement des POI aux lieux/micro-arrêts  

// correction fichier info
"CORRECTNESS_INFOFILE_ENABLED"         => false,

);


$PARAMS_MOBIKIDS = $PARAMS_ASTRO;
$PARAMS_MOBIKIDS["TRACKER_GPS_PERIOD"] = 1;
  