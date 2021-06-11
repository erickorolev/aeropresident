$("[data-fieldname=cf_1233]").live( "click", function() {
  if ($(this).prop('checked')==true)
  {
	$(".relatedBtnAddMore").click();
  }
  else
  {	  
	 $(this).prop( "checked", true );
  }
});


$(".relatedRecords").find("[data-fieldname=cf_1235]").closest("tr").hide();