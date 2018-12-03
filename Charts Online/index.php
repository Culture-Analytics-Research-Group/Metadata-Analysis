<!DOCTYPE php>

<head>
	<link href="line_chart_styles.css" rel="stylesheet" type="text/css">
</head>

<script>
	/*
		Copyright 2018 John W. Harlan working under Ana Jofre
		
		Licensed under the Apache License, Version 2.0 (the "License");
		you may not use this file except in compliance with the License.
		You may obtain a copy of the License at
		
		    http://www.apache.org/licenses/LICENSE-2.0
		
		Unless required by applicable law or agreed to in writing, software
		distributed under the License is distributed on an "AS IS" BASIS,
		WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
		See the License for the specific language governing permissions and
		limitations under the License.
		
		This script generates a dynamic form used to query a database in order to
			create line charts.
	*/

	//Adds another query to the form
	function add(event){
		var form = document.getElementById("Lines");
		form.appendChild(generateInterface(event));
	};

	//Actually does the hard work of adding the dropdowns and text to add another line to the form.
	function generateInterface(){
		//the lineId is used to identify the new form line from the others for events and POST.
		//    getTime() is used to ensure a unique name.
		var lineId = "line " + (new Date()).getTime();

		var newField = document.createElement("div");
		newField.id = lineId;
		newField.appendChild(document.createElement("span"));
		//Creates a dropdown for what field the user wants to investigate
		var fieldSelect = document.createElement("select");
		fieldSelect.innerHTML = "<option value=\"\" disabled selected>CHOOSE A FIELD</option>\
 					 <option value=\"multiface\">Multiface</option>\
					 <option value=\"color\">Color</option>\
					 <option value=\"category\">Category</option>\
					 <option value=\"photo\">Photo</option>\
					 <option value=\"angle\">Angle</option>\
					 <option value=\"gender\">Gender</option>\
					 <option value=\"race\">Race</option>\
					 <option value=\"adult\">Adult</option>\
					 <option value=\"smile\">Smiling</option>\
					 <option value=\"quality\">Quality</options>"
		fieldSelect.name = lineId + " field";
		fieldSelect.addEventListener("change", updateValue)
		newField.appendChild(fieldSelect);
		
		newField.appendChild(document.createElement("span"));
		
		//The dropdown for the value of the field. Gets populated when the user selects a field.
		var fieldValues = document.createElement("select");
		fieldValues.disabled = true;
		fieldValues.name = lineId + " value";
		
		newField.appendChild(fieldValues)
		var removeButton = document.createElement("BUTTON");
		removeButton.innerHTML = "Remove line";
		removeButton.addEventListener("click", removeLine);
		removeButton.type = "button";
		newField.appendChild(removeButton);
		
		//Write the context in between the dropdowns
		var cont = newField.getElementsByTagName("span");
		cont[0].innerHTML = "Draw a line where ";
		cont[1].innerHTML = " is ";
		
		//adds a little space
		newField.appendChild(document.createElement("p"));

		return newField;
	};

	//Removes the the specified form line
	function removeLine(event){
		if(document.getElementById("Lines").childElementCount > 3){
			var form = document.getElementById("Lines");
			form.removeChild(document.getElementById(event.path[1].id));
		} else {
			alert("You can't remove the last line!");
		}
	}

	//Populates the value dropdown for the changed field
	function updateValue(event){
		var fieldSelector = event.target;
		var fieldValueSelector = event.path[1].getElementsByTagName("select")[1];
		fieldValueSelector.disabled = true;
		switch(fieldSelector.value){
			case "multiface":
			case "color":
			case "photo":
			case "adult":
			case "smile":
				fieldValueSelector.innerHTML = "<option value=1>True</option>\
								<option value=0>False</option>"
			break;
			case "category":
				fieldValueSelector.innerHTML = "<option value=\"\'feature\'\">Feature</option>\
								<option value=\"\'cover\'\">Cover</option>\
								<option value=\"\'ad\'\">Ad</option>\
								<option value=\"\'author\'\">Author</option>"
			break;
			case "angle":
				fieldValueSelector.innerHTML = "<option value=1>Straight-on</option>\
								<option value=0>Profile</option>"
			break;
			case "gender":
				fieldValueSelector.innerHTML = "<option value=\"\'female\'\">Female</option>\
								<option value=\"\'male\'\">Male</option>\
								<option value=\"\'unknown\'\">Unknown</option>"
			break;
			case "race":
				fieldValueSelector.innerHTML = "<option value=\"\'americanindian\'\">American Indian</option>\
								<option value=\"\'asian\'\">Asian</option>\
								<option value=\"\'black\'\">Black</option>\
								<option value=\"\'pacificislander\'\">Pacific Islander</option>\
								<option value=\"\'white\'\">White</option>\
								<option value=\"\'unknown\'\">Unknown</option>"
			break;
			case "quality":
				fieldValueSelector.innerHTML = "<option value=\"\'good\'\">Good</option>\
								<option value=\"\'fair\'\">Fair</option>\
								<option value=\"\'poor\'\">Poor</option>\
								<option value=\"\'discard\'\">Discard</option>"
			break;
		}
		fieldValueSelector.disabled = false;
	};

	//Generates or deletes the subset dropdowns when necessary
	function subsetChange(event){
		if(event.target.checked){
			var form = document.getElementById("Subset");
			var newLine = document.createElement("div");
			newLine.id = "subsetInterface";
			newLine.appendChild(document.createElement("span"));

			//The selector for the subset field
			var fieldSelect = document.createElement("select");
			fieldSelect.name = "subsetFieldName";
			fieldSelect.innerHTML = "<option value=\"\" disabled selected>CHOOSE A FIELD</option>\
 						 <option value=\"multiface\">Multiface</option>\
						 <option value=\"color\">Color</option>\
						 <option value=\"category\">Category</option>\
						 <option value=\"photo\">Photo</option>\
						 <option value=\"angle\">Angle</option>\
						 <option value=\"gender\">Gender</option>\
						 <option value=\"race\">Race</option>\
						 <option value=\"adult\">Adult</option>\
						 <option value=\"smile\">Smiling</option>\
						 <option value=\"quality\">Quality</options>"
			fieldSelect.addEventListener("change", updateValue)
			newLine.appendChild(fieldSelect);

			newLine.appendChild(document.createElement("span"));

			//The selector for the value of the subset field, gets populated when the field selector is used
			var fieldValues = document.createElement("select");
			fieldValues.disabled = true;
			fieldValues.name = "subsetFieldValue";
			newLine.appendChild(fieldValues);

			//Writes the context between the dropdowns
			var cont = newLine.getElementsByTagName("span");
			cont[0].innerHTML = "Use the subset where ";
			cont[1].innerHTML = " is ";

			form.appendChild(newLine);
		} else {
			var form = document.getElementById("Subset");
			form.removeChild(document.getElementById("subsetInterface"));
		}
	};

</script>

<body bgcolor="gray" onload="add()" style="margin: 8px;">
	<form method="POST" target="_output" action="./chart.php" id="theForm">
		<fieldset>
			Time period:
			<select name="timeRes">
				<option value="yearly">Yearly</option>
				<option value="monthly">Monthly</option>
				<option value="issue">Per Issue</option>
			</select>
		</fieldset>
		<fieldset id="Subset">
			<input id="useSubset" type="checkbox" name="useSubset" value="true">Use subset</input>
			<script>
				document.getElementById("useSubset").addEventListener("change", subsetChange);
			</script>
		</fieldset>
		<fieldset id="Lines">
			<button type="button" id="addLineButton">Add another line</button>
			<script>
				document.getElementById("addLineButton").addEventListener("click", add);
			</script>
		</fieldset>
		<button type="submit" id="submitButton">Generate Chart!</button>
	</form>

	<!--This iframe houses the output charts after a delay. -->
	<iframe name="_output" width="100%" height="70%" style="border: 0px; margin: 0px; padding: 0px;"></iframe>
</body>
