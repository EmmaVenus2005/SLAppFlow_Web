SLAppFlow is an external app processors for Second Life objects. It allows PHP "flows" to be executed and send instructions to the SL object (like open_dialog, message_linked, etc. ). There is a SLAppFlow_Gateway LSL script that allows it to communicate. There is also an AppID securely set in the linkset. For obvious security reasons, the owner of the object can't change it. The SLAppFlow backend also get UserID from the headers provided by Linden Labs servers when getting an incoming request, so it provides a secure way to ensure the owner of the object can't change data from another user.

The AppID is the class of the object. Several objects owned by the same owner can have the same class. For example, an app "Tipjar". Since we have securely stored the AppID in the linkset, and user being identified by the header of the request, the SLAppFlow backend will only allow to access or write data for that combination of user and app. The "Tipjar" app can handle multiple objects from that class for a same user.

On the security point of view, there is a secret salt securely stored in the linkset, and each request sends a hashcode of request information and that secret salt. The server does the same calculation and compares it. If the value is not the same, the request is ignored.

The following diagram shows how the gateway works :

![SLAppFlow_Overview](https://github.com/user-attachments/assets/0d9acdfb-e213-47aa-8503-0f74f95c420c)

There are standard function types, like :
CF (Core Functions) : Should NEVER be used outside of other functions. Those allow direct database access or reading environement variables. In the future, I will work on isolating them.
SL (Second Life) : Used to send commands to Second Life through the object on which the flow runs. For example SLDialog, which opens a dialog box. There is always a first parameter $objectID, which is the object that gets the command. This will allow to find the URL of the object. Of course, it can't use an ID of an object from another user, the user being a context variable that can't be changed by the flow.
NV (Non Volatile) : Used to store or read data from the database. Those are always User and App specific. A set of NVSession instruction can have an additional layer of distinction, which allows for example multiple objects from the same class owned by the same owner.
AF (App Flow) : Generic reusable functions.

There is also a "webcontrol" feature I am implementing, allowing external control for the apps. This will have an authentication system, and include the same functions than the API, which will avoid double maintenance.

Below a diagram about how the web server is built :

<img width="900" alt="SLAppFlow_Web" src="https://github.com/user-attachments/assets/e7bfbc0b-974a-46d7-860a-57d9a5d4489c" />

Explanation if the files content :

![SLAppFlow_Folders](https://github.com/user-attachments/assets/d80f5af2-7048-4d89-83c1-37a8c338ed26)

index.php is the main file, which redirects depending of the subdomain (www.slappflow.net or api.slappflow.net).
api/apiindex.php is the main code of the API. Depenting of the request type, it includes "api/request/{requesttype}". In the future, there should only remain "seturl" and "flowstart". The first being used when the object becomes active to register its URL to the server, and the second one to start a "flow", which corresponds to an event on SL side (@touch, ...).
apps/{AppID}/flows : Contains the flows of an app.
apps/{AppID}/functions : Contains app-specific functions.
apps/{AppID}/web : Contains the web control app (index.php), and custom files that might be used by the web app.
