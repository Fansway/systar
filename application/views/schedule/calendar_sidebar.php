<?=$table_news?>
<? foreach($sidebar_tables as $sidebar_table){?>
<div>
<?=$this->table->generate($sidebar_table)?>
</div>
<? }?>