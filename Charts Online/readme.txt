This web page (index.php) is used to generate line graphs from database data
in a user-friendly way. It allows the user to select a subset of data to look
at (or not), and then allows them to add a number of different lines (representing 
percentages) to the graph, representing various data fields from the database. 
It accesses a live database.

After the user presses the "generate graphs" button, the database queries are 
made, and a graph will appear below the user interface. This is accomplished
using an iframe to load a separate web page (chart.php). The actual queries are performed
on the server in PHP by the secondary page that contains the output graph. The 
graphs are generated using javascript on the client machine. 

The user-interface is an adaptive form that can be modified in real time (adding 
new lines). This is accomplished using the javascript DOM to modify the page HTML.
To keep different sections self-contained and unique from each other, they are given
IDs based on the system time when they are generated. The data is sent to the secondary
page over POST.

For this web page to work, the system running it must have php installed, and have access 
to the database. If the page is supposed to available online, it must have web serving software
installed, such as the Apache webserver. 