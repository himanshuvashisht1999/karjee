
(function($) {


    $.fn.formToWizard = function() {

        var element = this;

        var steps = $(element).find(".fset");

		var count = steps.length;

        $(element).before("<ul id='steps'></ul>");

        steps.each(function(i) {
        	if(i==count-1){
				$(this).wrap("<div id='step" + i + "'></div>");
        	}
        	else{
				$(this).wrap("<div id='step" + i + "' class='pb-5'></div>");
        	}
            
            var name = $(this).find("h4").html();

			if(count>1)
			{
				if (i == 0) {

					createNextButton(i);

					selectStep(i);

				}

				else {

					$("#step" + i).hide();

					createPrevButton(i);

					createNextButton(i);

				}
			}
        });

        function createPrevButton(i) {

            var stepName = "step" + i;

            $("#" + stepName).append("<button  id='" + stepName + "Prev' class='Prev btn btn-deepblue mt-2'>Back</button>");

            $("#" + stepName + "Prev").bind("click", function(e) {
                $("#" + stepName).hide();
                $("#step" + (i - 1)).show();
                selectStep(i - 1);
            });

        }

        function createNextButton(i) {
			var stepName = "step" + i;
			var buttonName;
			if(i==0)
			{
				buttonName = "Proceed";	
				$("#" + stepName).append("<button  id='" + stepName + "Next' class='next btn btn-deepblue mt-2'>Proceed</button>");
			}
			else if(i==count-1)
			{
				buttonName = "Import";
				$("#" + stepName).append("<button  id='" + stepName + "Next' class='next btn btn-deepblue mt-2'>"+ buttonName +"</button>");
			}
			else
			{
				buttonName = "Next";
				$("#" + stepName).append("<button  id='" + stepName + "Next' class='next btn btn-deepblue mt-2'>"+ buttonName +"</button>");
			}            
				$("#" + stepName + "Next").bind("click", function(e) {
					if(i==count-1){
						savemapping();
						importrequest(excelarray,0,1000);
					}
					else{
						if(i==0){
							GetColHeaders(workbook,$('#xlsheet').val(),$('#xlhrow').val(),$('#xldrow').val(),tblfields);
						}					
						$("#" + stepName).hide();               
						$("#step" + (i + 1)).show();                
						selectStep(i + 1);
					}				
				});
        }

        function selectStep(i) {
            $("#steps li").removeClass("current");
        }
    }
})(jQuery); 
