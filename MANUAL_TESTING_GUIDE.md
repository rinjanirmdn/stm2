Vendor Request -> Admin Approval Workflow
=======================================

Implementation Complete.

Vendor Portal URL: /vendor/dashboard
Admin Booking URL: /bookings

Test Credentials:
-----------------
Vendor User:
Username: vendor_user
Password: password

Admin User:
Username: booking_admin
Password: password

Workflow Steps:
1. Login as Vendor.
2. Go to "New Booking".
3. Select Warehouse, Date, Time. System checks availability.
4. Submit Booking (Status: Pending Approval).
5. Login as Admin.
6. Go to "Bookings" menu.
7. View pending booking.
8. Action:
   - Approve -> Status becomes Scheduled. Email sent to Vendor.
   - Reject -> Status becomes Rejected. Email sent to Vendor.
   - Reschedule -> Admin picks new time -> Status becomes Pending Vendor Confirmation.
9. If Rescheduled:
   - Login as Vendor.
   - View Booking.
   - Click "Review & Confirm".
   - Accept or Reject or Propose New Time.

Notification Testing:
---------------------
1. New Booking Request: Admin receives in-app notification (bell icon).
2. Booking Approved/Rejected: Vendor receives in-app notification.
3. Reschedule: Vendor receives notification + action required badge.
4. Vendor Confirmation: Admin receives notification.

Check email (simulated via log or Mailtrap) for fallback notifications.
