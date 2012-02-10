<?php

// pref form
JToolBarHelper::preferences( 'com_helloscan' );

// config parameters
$params = &JComponentHelper::getParams('com_helloscan');

// authkey
$helloscan_authkey = $params->get('helloscan_authkey');
?>
<h2>Comment utiliser HelloScan pour Joomla</h2>

<h3>Configurer le composant Joomla</h3>

<?php if(empty($helloscan_authkey)): ?>
<p style="font-weight:bold; color:red;">Vous devez créer une clé d'authentification en cliquant sur paramètre. 
    Mettez une clé suffisament longue, intégrant des majuscules et des chiffres pour plus de sécurité</p>
<?php endif; ?>

<?php if(!empty($helloscan_authkey)): ?>
    <p style="color: green">Votre clé d'authentification pour HelloScan est <strong><?php echo $helloscan_authkey; ?></strong>
<?php endif; ?>

<h3>Configurer votre smartphone</h3>

<p>Pour découvrir comment configurer votre smartphone, 
    nous vous invitons à <a href="http://helloscan.mobi">consulter la documentation</a> 
        sur notre site <a href="http://helloscan.mobi">helloscan.mobi</a></p>
