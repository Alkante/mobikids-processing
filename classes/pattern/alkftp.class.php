<?php
/*licence/ 

Module écrit, supporté par la société Alkante SAS <alkante@alkante.com>

Nom du module : Alkanet::Class::Pattern
Module fournissant les classes de base Alkanet.
Ce module appartient au framework Alkanet.

Ce logiciel est régi par la licence CeCILL-C soumise au droit français et
respectant les principes de diffusion des logiciels libres. Vous pouvez
utiliser, modifier et/ou redistribuer ce programme sous les conditions
de la licence CeCILL-C telle que diffusée par le CEA, le CNRS et l'INRIA
sur le site http://www.cecill.info.

En contrepartie de l'accessibilité au code source et des droits de copie,
de modification et de redistribution accordés par cette licence, il n'est
offert aux utilisateurs qu'une garantie limitée. Pour les mêmes raisons,
seule une responsabilité restreinte pèse sur l'auteur du programme, le
titulaire des droits patrimoniaux et les concédants successifs.

A cet égard l'attention de l'utilisateur est attirée sur les risques
associés au chargement, à l'utilisation, à la modification et/ou au
développement et à la reproduction du logiciel par l'utilisateur étant
donné sa spécificité de logiciel libre, qui peut le rendre complexe à
manipuler et qui le réserve donc à des développeurs et des professionnels
avertis possédant des connaissances informatiques approfondies. Les
utilisateurs sont donc invités à charger et tester l'adéquation du
logiciel à leurs besoins dans des conditions permettant d'assurer la
sécurité de leurs systèmes et ou de leurs données et, plus généralement,
à l'utiliser et l'exploiter dans les mêmes conditions de sécurité.

Le fait que vous puissiez accéder à cet en-tête signifie que vous avez
pris connaissance de la licence CeCILL-C, et que vous en avez accepté les
termes.

/licence*/


/**
 * @package Alkanet_Class_Pattern
 * @class AlkFtp
 * 
 * Classe d'accès client à serveur de fichier via le protocole FTP
 */
class AlkFtp extends AlkObject 
{
  
  /** id de la connexion */
  protected $conn_id;

  /** ftp server */
  protected $ftp_server;
  
  /** ftp port */
  protected $ftp_port;
  
  /** ftp user name */
  protected $ftp_user_name;

  /** ftp user pass */
  protected $ftp_user_pass;

  /** resultat de la connexion */
  protected $login_result;
  
  /** last error */
  protected $last_error;
  
  /**
   * Constructeur par défaut.
   *
   */
  public function __construct($ftp_server, $ftp_user_name, $ftp_user_pass, $ftp_port=21) 
  {
    parent::__construct();
    $this->AlkFtp($ftp_server, $ftp_user_name, $ftp_user_pass);
  }
  
  /**
   * Constructeur par défaut.
   * 
   */
  public function AlkFtp($ftp_server, $ftp_user_name, $ftp_user_pass, $ftp_port=21)
  {
    $this->conn_id      = false;
    $this->login_result = false;
    $this->last_error   = "";
    
    $this->ftp_server = $ftp_server;
    $this->ftp_port   = $ftp_port;
    $this->ftp_user_name = $ftp_user_name;
    $this->ftp_user_pass = $ftp_user_pass;
    
    $this->connect();
    $this->login();
  }
  
  /**
   * Retourne l'id de la connexion
   * @return id ou false
   */
  public function getConnexionId()
  {
    return $this->conn_id;
  }
  
  /**
   * Indique si la connexion et l'indentification ont réussi
   *
   * @return unknown
   */
  public function isConnexionOk()
  {
    return ($this->conn_id!==false && $this->login_result!==false);
  }
  
  /**
   * Retourne la dernière erreur rencontrée
   *
   * @return unknown
   */
  public function getLastError()
  {
    return $this->last_error;
  }
  
  /**
   * Destructeur par défaut
   *
   */
  public function __destruct()
  {
    $this->close();
  }
  
  /**
   * Connexion au serveur ftp
   */
  private function connect()
  {
    $this->conn_id = @ftp_connect($this->ftp_server, $this->ftp_port);
    if( !$this->conn_id ) {
      $this->last_error = "Impossible d'établir la connexion au serveur ".$this->ftp_server;
      return false;
    }
    return true;
  }
  
  /**
   * Identification sur le serveur ftp
   */
  private function login()
  {
    $this->login_result = @ftp_login($this->conn_id, $this->ftp_user_name, $this->ftp_user_pass);
    if( !$this->conn_id || !$this->login_result) {
      $this->last_error = "Impossible de s'identifier sur le serveur ".$this->ftp_user_name."@".$this->ftp_server;
      return false;
    }
    return true;
  }

  /**
   * Fermeture de la connexion, si elle existe
   *
   */
  public function close()
  {
    if( $this->conn_id!==false ) {
      if( !@ftp_close($this->conn_id) ) {
        $this->last_error = "Impossible de fermer la connexion FTP ".$this->conn_id;
        return false;
      }
    }
    return true;
  }
  
  /**
   * Charge un fichier sur le serveur ftp
   *
   * @param unknown_type $remote_file
   * @param unknown_type $local_file
   * @param unknown_type $mode, FTP_BINARY ou FTP_ASCII
   */
  public function fput($remote_file, $local_file, $mode=FTP_BINARY)
  {
    if( !$this->isConnexionOk() ) return false;
    
    if($mode!=FTP_BINARY && $mode!=FTP_ASCII) {
      $this->last_error = "Mode FTP incorrect, utilisez FTP_BINARY ou FTP_ASCII ";
      return false;
    }
    
    $fp = @fopen($local_file, "r");
    if($fp !== false) {
    
      if( !@ftp_fput($this->conn_id, $remote_file, $fp, $mode) )
        $this->last_error = "Impossible de charger le fichier $local_file sur le serveur ".$this->ftp_user_name."@".$this->ftp_server;
      
      @fclose($fp);
      
    } else {
      $this->last_error = "Impossible d'ouvrir le fichier local $local_file en lecture";
      return false;
    }
    
    return true;
  }
  
  /**
   * Télécharger un fichier via ftp dans un fichier local
   *
   * @param unknown_type $local_file
   * @param unknown_type $remote_file
   * @param unknown_type $mode
   * @return unknown
   */
  function ftp_fget($local_file, $remote_file, $mode=FTP_BINARY)
  {
    if( !$this->isConnexionOk() ) return false;
    
    if($mode!=FTP_BINARY && $mode!=FTP_ASCII) {
      $this->last_error = "Mode FTP incorrect, utilisez FTP_BINARY ou FTP_ASCII ";
      return false;
    }
    
    $fp = @fopen($local_file, "w+");
    if($fp !== false) {
    
      if( !@ftp_fget($this->conn_id, $fp, $remote_file, $mode, 0) )
        $this->last_error = "Impossible de télécharger le fichier $remote_file sur le serveur ".$this->ftp_user_name."@".$this->ftp_server;
      
      @fclose($fp);
      
    } else {
      $this->last_error = "Impossible d'ouvrir le fichier local $local_file en écriture";
      return false;
    }
    
    return true;
  }
  
  /**
   * Modifie le dossier courant
   *
   * @param unknown_type $directory
   */
  public function chdir($directory=".")
  {
    if( !$this->isConnexionOk() ) return false;
    
    if( !@ftp_chdir($this->conn_id, $directory) ) {
      $this->last_error = "Impossible de changer de répertoire courant";
      return false;
    }
    return true;
  }
  
  /**
   * Retourne la liste des fichiers d'un dossier
   * 
   * @param unknown_type $directory
   */
  public function nlist($directory=".")
  {
    if( !$this->isConnexionOk() ) return false;
    
    $ret = @ftp_nlist($this->conn_id, $directory);
    if( $ret===false ) {
      $this->last_error = "Impossible de lister le répertoire $directory";
      return false;
    }
    return $ret;
  }
  
  /**
   * Retourne le nom du dossier courant
   *
   */
  public function pwd()
  {
    if( !$this->isConnexionOk() ) return false;
    
    return @ftp_pwd($this->conn_id);
  }
  
}
?>