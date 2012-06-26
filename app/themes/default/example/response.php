<?php $title = 'Exo\Response Object'; ?>
<?php include($this->theme_path . '/inc/header.php'); ?>

<h1>Exo\Response Object</h1>
<p>Instantiated by the View class, the Response object is what is sent back to the requesting user agent.</p>
<p>Using some magical double-calling, here is an example of what is generated by this very page-- minus the content, because that would look weird.</p>
<pre>
<?php var_dump($response); ?>
</pre>

<?php include($this->theme_path . '/inc/footer.php'); ?>
