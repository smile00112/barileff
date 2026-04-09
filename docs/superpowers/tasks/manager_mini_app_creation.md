For the project, you need to create a mini-application for managing orders by api managers.
The application must:
-authorize the user with the manager role
-display orders linked to the inventory source as a manager (when creating, the inventory source is linked to the manager to manage only his warehouse)
-there should be a function for filtering orders by date, client, status
-visually, orders should be compact and drop-down. When folded, output the order number, date-time, and customer's name. In the expanded form, display the full information on the order
-You need to be able to change the order status
-the style of the application is of your choice. It should be modern and light.
-vue 3 stack
-there should be push notifications for notifying about a new order and changing the status of existing ones. However, information about orders must be provided within the user's warehouse.
-the order list needs to be updated in the background. That is, in an open application, the list of orders must be updated live when a new order is received or the old data is changed
-the application must be installed as a PWA.

on the backup, implement an api for interacting with the application in a separate package.
