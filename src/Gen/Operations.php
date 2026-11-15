<?php

// Generated from docs/api/open-api.yaml by sdks/tools (pnpm sdk:gen) — do not edit.
namespace IChinese\Open\Gen;

trait Operations
{
    /** The current user (user token) (GET /user/info; user token; scopes: openid). */
    public function getUserInfo($options = [])
    {
        return $this->call('getUserInfo', 'GET', '/user/info', [], null, null, ['user'], $options);
    }

    /** The institution that installed the app (tenant token) (GET /tenant; tenant token; scopes: ). */
    public function getTenant($options = [])
    {
        return $this->call('getTenant', 'GET', '/tenant', [], null, null, ['tenant'], $options);
    }

    /** The institution's members (directory); members in all regions are listed together, without revealing regions (GET /tenant/members; tenant token; scopes: org:member:readonly, org:member). */
    public function listTenantMembers($query = null, $options = [])
    {
        return $this->call('listTenantMembers', 'GET', '/tenant/members', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listTenantMembers 的全部 items；page_size 默认 100。 */
    public function listTenantMembersAll($query = null, $options = [])
    {
        return $this->iterate('listTenantMembers', 'GET', '/tenant/members', [], $query, ['tenant'], $options);
    }

    /** Invite a member (they join only after accepting, the same as inviting in the institution admin) (POST /tenant/invitations; tenant token; scopes: org:member). */
    public function inviteTenantMember($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('inviteTenantMember: body is required');
        return $this->call('inviteTenantMember', 'POST', '/tenant/invitations', [], null, $body, ['tenant'], $options);
    }

    /** Classes: with a tenant token, all of the institution's classes; with a user token, the classes this user teaches (GET /classes; tenant/user token; scopes: class:readonly, class:member). */
    public function listClasses($query = null, $options = [])
    {
        return $this->call('listClasses', 'GET', '/classes', [], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listClasses 的全部 items；page_size 默认 100。 */
    public function listClassesAll($query = null, $options = [])
    {
        return $this->iterate('listClasses', 'GET', '/classes', [], $query, ['tenant', 'user'], $options);
    }

    /** A class (GET /classes/{class_id}; tenant/user token; scopes: class:readonly, class:member). */
    public function getClass($classId, $options = [])
    {
        return $this->call('getClass', 'GET', '/classes/{class_id}', ['class_id' => $classId], null, null, ['tenant', 'user'], $options);
    }

    /** Roster (teachers and learners) (GET /classes/{class_id}/members; tenant/user token; scopes: class:readonly, class:member). */
    public function listClassMembers($classId, $query = null, $options = [])
    {
        return $this->call('listClassMembers', 'GET', '/classes/{class_id}/members', ['class_id' => $classId], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listClassMembers 的全部 items；page_size 默认 100。 */
    public function listClassMembersAll($classId, $query = null, $options = [])
    {
        return $this->iterate('listClassMembers', 'GET', '/classes/{class_id}/members', ['class_id' => $classId], $query, ['tenant', 'user'], $options);
    }

    /** Add one of the institution's members to a class (as a learner) (POST /classes/{class_id}/members; tenant/user token; scopes: class:member). */
    public function addClassMember($classId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('addClassMember: body is required');
        return $this->call('addClassMember', 'POST', '/classes/{class_id}/members', ['class_id' => $classId], null, $body, ['tenant', 'user'], $options);
    }

    /** Remove a learner from a class (DELETE /classes/{class_id}/members/{open_id}; tenant/user token; scopes: class:member). */
    public function removeClassMember($classId, $openId, $options = [])
    {
        return $this->call('removeClassMember', 'DELETE', '/classes/{class_id}/members/{open_id}', ['class_id' => $classId, 'open_id' => $openId], null, null, ['tenant', 'user'], $options);
    }

    /** A class's sessions (within a time range of at most 100 days) (GET /classes/{class_id}/sessions; tenant/user token; scopes: schedule:readonly, schedule). */
    public function listClassSessions($classId, $query = null, $options = [])
    {
        if (!isset($query['from'])) throw new \InvalidArgumentException('listClassSessions: from is required');
        if (!isset($query['to'])) throw new \InvalidArgumentException('listClassSessions: to is required');
        return $this->call('listClassSessions', 'GET', '/classes/{class_id}/sessions', ['class_id' => $classId], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listClassSessions 的全部 items；page_size 默认 100。 */
    public function listClassSessionsAll($classId, $query = null, $options = [])
    {
        return $this->iterate('listClassSessions', 'GET', '/classes/{class_id}/sessions', ['class_id' => $classId], $query, ['tenant', 'user'], $options);
    }

    /** Schedule a class's sessions (once, or repeating weekly), online or at one of the institution's teaching sites (POST /classes/{class_id}/sessions; tenant/user token; scopes: schedule). */
    public function createClassSessions($classId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createClassSessions: body is required');
        return $this->call('createClassSessions', 'POST', '/classes/{class_id}/sessions', ['class_id' => $classId], null, $body, ['tenant', 'user'], $options);
    }

    /** Change where one session (or this one and the later ones of its series) is held (PUT /classes/{class_id}/sessions/{session_id}/place; tenant/user token; scopes: schedule). */
    public function setClassSessionPlace($classId, $sessionId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('setClassSessionPlace: body is required');
        return $this->call('setClassSessionPlace', 'PUT', '/classes/{class_id}/sessions/{session_id}/place', ['class_id' => $classId, 'session_id' => $sessionId], null, $body, ['tenant', 'user'], $options);
    }

    /** Cancel a class session (members are notified, the same as cancelling in the timetable) (POST /classes/{class_id}/sessions/{session_id}/cancel; tenant/user token; scopes: schedule). */
    public function cancelClassSession($classId, $sessionId, $options = [])
    {
        return $this->call('cancelClassSession', 'POST', '/classes/{class_id}/sessions/{session_id}/cancel', ['class_id' => $classId, 'session_id' => $sessionId], null, null, ['tenant', 'user'], $options);
    }

    /** Reschedule a class session (POST /classes/{class_id}/sessions/{session_id}/reschedule; tenant/user token; scopes: schedule). */
    public function rescheduleClassSession($classId, $sessionId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('rescheduleClassSession: body is required');
        return $this->call('rescheduleClassSession', 'POST', '/classes/{class_id}/sessions/{session_id}/reschedule', ['class_id' => $classId, 'session_id' => $sessionId], null, $body, ['tenant', 'user'], $options);
    }

    /** Attendance of a class session (who came, and how long they stayed in the classroom) (GET /classes/{class_id}/sessions/{session_id}/attendance; tenant/user token; scopes: attendance:readonly). */
    public function getSessionAttendance($classId, $sessionId, $options = [])
    {
        return $this->call('getSessionAttendance', 'GET', '/classes/{class_id}/sessions/{session_id}/attendance', ['class_id' => $classId, 'session_id' => $sessionId], null, null, ['tenant', 'user'], $options);
    }

    /** A class's assignments and exams (GET /classes/{class_id}/assignments; tenant/user token; scopes: coursework, grade:readonly). */
    public function listAssignments($classId, $query = null, $options = [])
    {
        return $this->call('listAssignments', 'GET', '/classes/{class_id}/assignments', ['class_id' => $classId], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listAssignments 的全部 items；page_size 默认 100。 */
    public function listAssignmentsAll($classId, $query = null, $options = [])
    {
        return $this->iterate('listAssignments', 'GET', '/classes/{class_id}/assignments', ['class_id' => $classId], $query, ['tenant', 'user'], $options);
    }

    /** Create an assignment (text instructions + due time; questions are edited in the platform). With a user token, the user must be a teacher of this class (POST /classes/{class_id}/assignments; tenant/user token; scopes: coursework). */
    public function createAssignment($classId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createAssignment: body is required');
        return $this->call('createAssignment', 'POST', '/classes/{class_id}/assignments', ['class_id' => $classId], null, $body, ['tenant', 'user'], $options);
    }

    /** An assignment's submissions and scores (GET /classes/{class_id}/assignments/{assignment_id}/submissions; tenant/user token; scopes: grade:readonly). */
    public function listSubmissions($classId, $assignmentId, $query = null, $options = [])
    {
        return $this->call('listSubmissions', 'GET', '/classes/{class_id}/assignments/{assignment_id}/submissions', ['class_id' => $classId, 'assignment_id' => $assignmentId], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listSubmissions 的全部 items；page_size 默认 100。 */
    public function listSubmissionsAll($classId, $assignmentId, $query = null, $options = [])
    {
        return $this->iterate('listSubmissions', 'GET', '/classes/{class_id}/assignments/{assignment_id}/submissions', ['class_id' => $classId, 'assignment_id' => $assignmentId], $query, ['tenant', 'user'], $options);
    }

    /** Write back a score (the result of external grading); the learner is notified that grades are published (PUT /classes/{class_id}/assignments/{assignment_id}/submissions/{open_id}/score; tenant/user token; scopes: coursework). */
    public function setSubmissionScore($classId, $assignmentId, $openId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('setSubmissionScore: body is required');
        return $this->call('setSubmissionScore', 'PUT', '/classes/{class_id}/assignments/{assignment_id}/submissions/{open_id}/score', ['class_id' => $classId, 'assignment_id' => $assignmentId, 'open_id' => $openId], null, $body, ['tenant', 'user'], $options);
    }

    /** Export grades (one row per learner per assignment) (GET /classes/{class_id}/grades; tenant/user token; scopes: grade:readonly). */
    public function exportClassGrades($classId, $query = null, $options = [])
    {
        return $this->call('exportClassGrades', 'GET', '/classes/{class_id}/grades', ['class_id' => $classId], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 exportClassGrades 的全部 items；page_size 默认 100。 */
    public function exportClassGradesAll($classId, $query = null, $options = [])
    {
        return $this->iterate('exportClassGrades', 'GET', '/classes/{class_id}/grades', ['class_id' => $classId], $query, ['tenant', 'user'], $options);
    }

    /** The institution's recorded courses (tenant token) or the courses this user authored (user token) (GET /courses; tenant/user token; scopes: course, learning:progress:readonly). */
    public function listCourses($query = null, $options = [])
    {
        return $this->call('listCourses', 'GET', '/courses', [], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listCourses 的全部 items；page_size 默认 100。 */
    public function listCoursesAll($query = null, $options = [])
    {
        return $this->iterate('listCourses', 'GET', '/courses', [], $query, ['tenant', 'user'], $options);
    }

    /** Course outline (titles and order of chapters and lessons) (GET /courses/{course_id}; tenant/user token; scopes: course). */
    public function getCourseOutline($courseId, $options = [])
    {
        return $this->call('getCourseOutline', 'GET', '/courses/{course_id}', ['course_id' => $courseId], null, null, ['tenant', 'user'], $options);
    }

    /** Change a course's title and introduction; a published course that is changed goes through review again as usual (PATCH /courses/{course_id}; tenant/user token; scopes: course). */
    public function updateCourseOutline($courseId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('updateCourseOutline: body is required');
        return $this->call('updateCourseOutline', 'PATCH', '/courses/{course_id}', ['course_id' => $courseId], null, $body, ['tenant', 'user'], $options);
    }

    /** Learners' learning progress (number of lessons completed). A user token can only see its own (GET /courses/{course_id}/progress; tenant/user token; scopes: learning:progress:readonly). */
    public function listCourseProgress($courseId, $query = null, $options = [])
    {
        return $this->call('listCourseProgress', 'GET', '/courses/{course_id}/progress', ['course_id' => $courseId], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listCourseProgress 的全部 items；page_size 默认 100。 */
    public function listCourseProgressAll($courseId, $query = null, $options = [])
    {
        return $this->iterate('listCourseProgress', 'GET', '/courses/{course_id}/progress', ['course_id' => $courseId], $query, ['tenant', 'user'], $options);
    }

    /** Send a bot message (text or card) to a group chat the bot is in, or privately to a user who installed the app (POST /im/messages; tenant/app token; scopes: im:message:send_as_bot). */
    public function sendBotMessage($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('sendBotMessage: body is required');
        return $this->call('sendBotMessage', 'POST', '/im/messages', [], null, $body, ['tenant', 'app'], $options);
    }

    /** Group chats the bot is in (GET /im/chats; tenant/app token; scopes: im:chat:readonly). */
    public function listBotChats($query = null, $options = [])
    {
        return $this->call('listBotChats', 'GET', '/im/chats', [], $query, null, ['tenant', 'app'], $options);
    }

    /** 逐页读取 listBotChats 的全部 items；page_size 默认 100。 */
    public function listBotChatsAll($query = null, $options = [])
    {
        return $this->iterate('listBotChats', 'GET', '/im/chats', [], $query, ['tenant', 'app'], $options);
    }

    /** Send a notification to members of this institution (it goes to "System notifications" and is pushed according to each recipient's notification settings and quiet hours) (POST /notifications; tenant token; scopes: notify:send). */
    public function sendNotification($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('sendNotification: body is required');
        return $this->call('sendNotification', 'POST', '/notifications', [], null, $body, ['tenant'], $options);
    }

    /** Replays and post-class summaries of a class's live sessions (no playback URL; `watch_url` opens the replay page in the platform) (GET /classes/{class_id}/recordings; tenant/user token; scopes: live:recording:readonly). */
    public function listClassRecordings($classId, $query = null, $options = [])
    {
        return $this->call('listClassRecordings', 'GET', '/classes/{class_id}/recordings', ['class_id' => $classId], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listClassRecordings 的全部 items；page_size 默认 100。 */
    public function listClassRecordingsAll($classId, $query = null, $options = [])
    {
        return $this->iterate('listClassRecordings', 'GET', '/classes/{class_id}/recordings', ['class_id' => $classId], $query, ['tenant', 'user'], $options);
    }

    /** The institution's events (including drafts and those pending review) (GET /events; tenant token; scopes: event, event:registration:readonly). */
    public function listTenantEvents($query = null, $options = [])
    {
        return $this->call('listTenantEvents', 'GET', '/events', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listTenantEvents 的全部 items；page_size 默认 100。 */
    public function listTenantEventsAll($query = null, $options = [])
    {
        return $this->iterate('listTenantEvents', 'GET', '/events', [], $query, ['tenant'], $options);
    }

    /** Create an event (a draft; the institution publishes it in its console, moderated as usual) (POST /events; tenant token; scopes: event). */
    public function createTenantEvent($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createTenantEvent: body is required');
        return $this->call('createTenantEvent', 'POST', '/events', [], null, $body, ['tenant'], $options);
    }

    /** Change an event (the same fields as event editing in the platform's institution console) (PATCH /events/{event_id}; tenant token; scopes: event). */
    public function updateTenantEvent($eventId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('updateTenantEvent: body is required');
        return $this->call('updateTenantEvent', 'PATCH', '/events/{event_id}', ['event_id' => $eventId], null, $body, ['tenant'], $options);
    }

    /** An event's registrations and check-ins (GET /events/{event_id}/registrations; tenant token; scopes: event:registration:readonly). */
    public function listEventRegistrations($eventId, $query = null, $options = [])
    {
        return $this->call('listEventRegistrations', 'GET', '/events/{event_id}/registrations', ['event_id' => $eventId], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listEventRegistrations 的全部 items；page_size 默认 100。 */
    public function listEventRegistrationsAll($eventId, $query = null, $options = [])
    {
        return $this->iterate('listEventRegistrations', 'GET', '/events/{event_id}/registrations', ['event_id' => $eventId], $query, ['tenant'], $options);
    }

    /** The institution's job postings (including drafts and those pending review) (GET /jobs; tenant token; scopes: job, job:candidate:readonly). */
    public function listTenantJobs($query = null, $options = [])
    {
        return $this->call('listTenantJobs', 'GET', '/jobs', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listTenantJobs 的全部 items；page_size 默认 100。 */
    public function listTenantJobsAll($query = null, $options = [])
    {
        return $this->iterate('listTenantJobs', 'GET', '/jobs', [], $query, ['tenant'], $options);
    }

    /** Sync in a job posting (as a draft; submitting and publishing still go through the institution's review and NetEase Yidun automatic review) (POST /jobs; tenant token; scopes: job). */
    public function createTenantJob($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createTenantJob: body is required');
        return $this->call('createTenantJob', 'POST', '/jobs', [], null, $body, ['tenant'], $options);
    }

    /** A job's candidates (the fields they agreed to give the institution when applying) (GET /jobs/{job_id}/candidates; tenant token; scopes: job:candidate:readonly). */
    public function listJobCandidates($jobId, $query = null, $options = [])
    {
        return $this->call('listJobCandidates', 'GET', '/jobs/{job_id}/candidates', ['job_id' => $jobId], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listJobCandidates 的全部 items；page_size 默认 100。 */
    public function listJobCandidatesAll($jobId, $query = null, $options = [])
    {
        return $this->iterate('listJobCandidates', 'GET', '/jobs/{job_id}/candidates', ['job_id' => $jobId], $query, ['tenant'], $options);
    }

    /** The institution's teaching sites (use their venue_id to schedule trial slots) (GET /trial/venues; tenant token; scopes: trial). */
    public function listTrialVenues($query = null, $options = [])
    {
        return $this->call('listTrialVenues', 'GET', '/trial/venues', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listTrialVenues 的全部 items；page_size 默认 100。 */
    public function listTrialVenuesAll($query = null, $options = [])
    {
        return $this->iterate('listTrialVenues', 'GET', '/trial/venues', [], $query, ['tenant'], $options);
    }

    /** Trial times (a trial lesson's own times at teaching sites; by default only those not over yet, by time) (GET /trial/slots; tenant token; scopes: trial). */
    public function listTrialSlots($query = null, $options = [])
    {
        return $this->call('listTrialSlots', 'GET', '/trial/slots', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listTrialSlots 的全部 items；page_size 默认 100。 */
    public function listTrialSlotsAll($query = null, $options = [])
    {
        return $this->iterate('listTrialSlots', 'GET', '/trial/slots', [], $query, ['tenant'], $options);
    }

    /** Open trial times (they can repeat weekly): add them to an existing trial lesson, or open a new trial draft (POST /trial/slots; tenant token; scopes: trial). */
    public function createTrialSlots($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createTrialSlots: body is required');
        return $this->call('createTrialSlots', 'POST', '/trial/slots', [], null, $body, ['tenant'], $options);
    }

    /** Close a trial time (no more bookings; bookings already made stay as they are, and nobody is notified) (POST /trial/slots/{slot_id}/cancel; tenant token; scopes: trial). */
    public function cancelTrialSlot($slotId, $body = null, $options = [])
    {
        return $this->call('cancelTrialSlot', 'POST', '/trial/slots/{slot_id}/cancel', ['slot_id' => $slotId], null, $body, ['tenant'], $options);
    }

    /** Fill in (or change) the teacher of a trial draft (PUT /trial/openings/{opening_id}/teacher; tenant token; scopes: trial). */
    public function setTrialTeacher($openingId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('setTrialTeacher: body is required');
        return $this->call('setTrialTeacher', 'PUT', '/trial/openings/{opening_id}/teacher', ['opening_id' => $openingId], null, $body, ['tenant'], $options);
    }

    /** Trial lesson bookings at the institution's teaching sites (GET /trial/bookings; tenant token; scopes: trial). */
    public function listTrialBookings($query = null, $options = [])
    {
        return $this->call('listTrialBookings', 'GET', '/trial/bookings', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listTrialBookings 的全部 items；page_size 默认 100。 */
    public function listTrialBookingsAll($query = null, $options = [])
    {
        return $this->iterate('listTrialBookings', 'GET', '/trial/bookings', [], $query, ['tenant'], $options);
    }

    /** Confirm a trial lesson booking (POST /trial/bookings/{booking_id}/confirm; tenant token; scopes: trial). */
    public function confirmTrialBooking($bookingId, $options = [])
    {
        return $this->call('confirmTrialBooking', 'POST', '/trial/bookings/{booking_id}/confirm', ['booking_id' => $bookingId], null, null, ['tenant'], $options);
    }

    /** The institution's teaching sites (campuses), newest first (GET /venues; tenant token; scopes: venue:readonly, venue, trial). */
    public function listVenues($query = null, $options = [])
    {
        return $this->call('listVenues', 'GET', '/venues', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listVenues 的全部 items；page_size 默认 100。 */
    public function listVenuesAll($query = null, $options = [])
    {
        return $this->iterate('listVenues', 'GET', '/venues', [], $query, ['tenant'], $options);
    }

    /** Create a draft teaching site (campus); it is published in the platform and reviewed as usual (POST /venues; tenant token; scopes: venue). */
    public function createVenue($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createVenue: body is required');
        return $this->call('createVenue', 'POST', '/venues', [], null, $body, ['tenant'], $options);
    }

    /** One teaching site (of this institution only) (GET /venues/{venue_id}; tenant token; scopes: venue:readonly, venue, trial). */
    public function getVenue($venueId, $options = [])
    {
        return $this->call('getVenue', 'GET', '/venues/{venue_id}', ['venue_id' => $venueId], null, null, ['tenant'], $options);
    }

    /** Openings: every class and course opened (group classes, one-to-ones, open classes, trial lessons, video courses), newest first (GET /openings; tenant/user token; scopes: opening:readonly). */
    public function listOpenings($query = null, $options = [])
    {
        return $this->call('listOpenings', 'GET', '/openings', [], $query, null, ['tenant', 'user'], $options);
    }

    /** 逐页读取 listOpenings 的全部 items；page_size 默认 100。 */
    public function listOpeningsAll($query = null, $options = [])
    {
        return $this->iterate('listOpenings', 'GET', '/openings', [], $query, ['tenant', 'user'], $options);
    }

    /** One opening (GET /openings/{opening_id}; tenant/user token; scopes: opening:readonly). */
    public function getOpening($openingId, $options = [])
    {
        return $this->call('getOpening', 'GET', '/openings/{opening_id}', ['opening_id' => $openingId], null, null, ['tenant', 'user'], $options);
    }

    /** One page of the institution's dashboard (read only): enrolment, teaching, teaching sites, income, community and events (GET /insights; tenant token; scopes: insights:readonly). */
    public function getInsights($query = null, $options = [])
    {
        return $this->call('getInsights', 'GET', '/insights', [], $query, null, ['tenant'], $options);
    }

    /** The institution's orders (read-only, for reconciliation). Write operations involving money are not open (GET /trade/orders; tenant token; scopes: trade:order:readonly). */
    public function listTradeOrders($query = null, $options = [])
    {
        return $this->call('listTradeOrders', 'GET', '/trade/orders', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listTradeOrders 的全部 items；page_size 默认 100。 */
    public function listTradeOrdersAll($query = null, $options = [])
    {
        return $this->iterate('listTradeOrders', 'GET', '/trade/orders', [], $query, ['tenant'], $options);
    }

    /** The institution's bookings (one-to-ones and trial lessons, read only for reconciliation), latest lesson first (GET /bookings; tenant token; scopes: trade:booking:readonly). */
    public function listBookings($query = null, $options = [])
    {
        return $this->call('listBookings', 'GET', '/bookings', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listBookings 的全部 items；page_size 默认 100。 */
    public function listBookingsAll($query = null, $options = [])
    {
        return $this->iterate('listBookings', 'GET', '/bookings', [], $query, ['tenant'], $options);
    }

    /** One booked lesson (GET /bookings/{booking_id}; tenant token; scopes: trade:booking:readonly). */
    public function getBooking($bookingId, $options = [])
    {
        return $this->call('getBooking', 'GET', '/bookings/{booking_id}', ['booking_id' => $bookingId], null, null, ['tenant'], $options);
    }

    /** Reviews of this institution (and its courses and teaching sites) (GET /reviews; tenant token; scopes: review). */
    public function listTenantReviews($query = null, $options = [])
    {
        return $this->call('listTenantReviews', 'GET', '/reviews', [], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listTenantReviews 的全部 items；page_size 默认 100。 */
    public function listTenantReviewsAll($query = null, $options = [])
    {
        return $this->iterate('listTenantReviews', 'GET', '/reviews', [], $query, ['tenant'], $options);
    }

    /** Reply to a review on behalf of the institution (one reply per review; the content is moderated as usual) (POST /reviews/{review_id}/reply; tenant token; scopes: review). */
    public function replyReview($reviewId, $body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('replyReview: body is required');
        return $this->call('replyReview', 'POST', '/reviews/{review_id}/reply', ['review_id' => $reviewId], null, $body, ['tenant'], $options);
    }

    /** Public courses, events and jobs shown in this region (GET /public/{kind}; app/tenant/user token; scopes: ). */
    public function listPublicContent($kind, $query = null, $options = [])
    {
        return $this->call('listPublicContent', 'GET', '/public/{kind}', ['kind' => $kind], $query, null, ['app', 'tenant', 'user'], $options);
    }

    /** 逐页读取 listPublicContent 的全部 items；page_size 默认 100。 */
    public function listPublicContentAll($kind, $query = null, $options = [])
    {
        return $this->iterate('listPublicContent', 'GET', '/public/{kind}', ['kind' => $kind], $query, ['app', 'tenant', 'user'], $options);
    }

    /** The user's interest profile (weighted tags of topics and content types; raw browsing and interaction records are not given) (GET /user/profile/interests; user token; scopes: profile:interest:readonly). */
    public function getUserInterests($options = [])
    {
        return $this->call('getUserInterests', 'GET', '/user/profile/interests', [], null, null, ['user'], $options);
    }

    /** Add an interest signal with its source (POST /user/profile/interest_signals; user token; scopes: profile:interest). */
    public function addInterestSignal($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('addInterestSignal: body is required');
        return $this->call('addInterestSignal', 'POST', '/user/profile/interest_signals', [], null, $body, ['user'], $options);
    }

    /** The user's learning profile (level, goals, languages being learned) (GET /user/profile/learning; user token; scopes: profile:learning:readonly). */
    public function getUserLearningProfile($options = [])
    {
        return $this->call('getUserLearningProfile', 'GET', '/user/profile/learning', [], null, null, ['user'], $options);
    }

    /** Report a piece of public content on behalf of a user (it enters the platform's moderation queue; the user can see it in their own report history) (POST /reports; user token; scopes: report:create). */
    public function reportOnBehalf($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('reportOnBehalf: body is required');
        return $this->call('reportOnBehalf', 'POST', '/reports', [], null, $body, ['user'], $options);
    }

    /** Get an upload URL for an image (it is re-encoded and its EXIF removed when the post is made) (POST /community/images; user/tenant token; scopes: community:post). */
    public function createCommunityImageUpload($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createCommunityImageUpload: body is required');
        return $this->call('createCommunityImageUpload', 'POST', '/community/images', [], null, $body, ['user', 'tenant'], $options);
    }

    /** Get an upload URL for a video (up to 4 GB, 1 hour) (POST /community/videos; user/tenant token; scopes: community:post). */
    public function createCommunityVideoUpload($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createCommunityVideoUpload: body is required');
        return $this->call('createCommunityVideoUpload', 'POST', '/community/videos', [], null, $body, ['user', 'tenant'], $options);
    }

    /** The upload is done; start transcoding (calling it again has no side effects) (POST /community/videos/{asset_id}/complete; user/tenant token; scopes: community:post). */
    public function completeCommunityVideoUpload($assetId, $options = [])
    {
        return $this->call('completeCommunityVideoUpload', 'POST', '/community/videos/{asset_id}/complete', ['asset_id' => $assetId], null, null, ['user', 'tenant'], $options);
    }

    /** Transcoding status (only for videos this app uploaded for this person) (GET /community/videos/{asset_id}; user/tenant token; scopes: community:post). */
    public function getCommunityVideo($assetId, $options = [])
    {
        return $this->call('getCommunityVideo', 'GET', '/community/videos/{asset_id}', ['asset_id' => $assetId], null, null, ['user', 'tenant'], $options);
    }

    /** Posts this app made in this person's name (the person's own posts and other apps' posts are not visible) (GET /community/posts; user/tenant token; scopes: community:post). */
    public function listCommunityPosts($query = null, $options = [])
    {
        return $this->call('listCommunityPosts', 'GET', '/community/posts', [], $query, null, ['user', 'tenant'], $options);
    }

    /** 逐页读取 listCommunityPosts 的全部 items；page_size 默认 100。 */
    public function listCommunityPostsAll($query = null, $options = [])
    {
        return $this->iterate('listCommunityPosts', 'GET', '/community/posts', [], $query, ['user', 'tenant'], $options);
    }

    /** Publish a public post in this person's name (text, up to 9 images or 1 video, up to 10 topics) (POST /community/posts; user/tenant token; scopes: community:post). */
    public function createCommunityPost($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('createCommunityPost: body is required');
        return $this->call('createCommunityPost', 'POST', '/community/posts', [], null, $body, ['user', 'tenant'], $options);
    }

    /** The status of one post (only posts this app made; anything else is 404) (GET /community/posts/{post_id}; user/tenant token; scopes: community:post). */
    public function getCommunityPost($postId, $options = [])
    {
        return $this->call('getCommunityPost', 'GET', '/community/posts/{post_id}', ['post_id' => $postId], null, null, ['user', 'tenant'], $options);
    }

    /** Delete a post (only posts this app made; anything else is 404) (DELETE /community/posts/{post_id}; user/tenant token; scopes: community:post). */
    public function deleteCommunityPost($postId, $options = [])
    {
        return $this->call('deleteCommunityPost', 'DELETE', '/community/posts/{post_id}', ['post_id' => $postId], null, null, ['user', 'tenant'], $options);
    }

    /** Categories a post can be filed under (those enabled in this region) (GET /community/categories; user/tenant token; scopes: community:post). */
    public function listCommunityCategories($options = [])
    {
        return $this->call('listCommunityCategories', 'GET', '/community/categories', [], null, null, ['user', 'tenant'], $options);
    }

    /** This person's timetable: their sessions, the events they signed up for, confirmed trial lessons and exams (for calendar apps; read-only) (GET /me/schedule; user token; scopes: me:schedule:readonly). */
    public function getMySchedule($query = null, $options = [])
    {
        if (!isset($query['from'])) throw new \InvalidArgumentException('getMySchedule: from is required');
        if (!isset($query['to'])) throw new \InvalidArgumentException('getMySchedule: to is required');
        return $this->call('getMySchedule', 'GET', '/me/schedule', [], $query, null, ['user'], $options);
    }

    /** 逐页读取 getMySchedule 的全部 items；page_size 默认 100。 */
    public function getMyScheduleAll($query = null, $options = [])
    {
        return $this->iterate('getMySchedule', 'GET', '/me/schedule', [], $query, ['user'], $options);
    }

    /** The courses this person is studying and their progress (lessons completed, last studied; level and goals belong to the gold-only profile and are not here) (GET /me/courses; user token; scopes: me:learning:readonly). */
    public function listMyCourses($query = null, $options = [])
    {
        return $this->call('listMyCourses', 'GET', '/me/courses', [], $query, null, ['user'], $options);
    }

    /** 逐页读取 listMyCourses 的全部 items；page_size 默认 100。 */
    public function listMyCoursesAll($query = null, $options = [])
    {
        return $this->iterate('listMyCourses', 'GET', '/me/courses', [], $query, ['user'], $options);
    }

    /** This person's word bank: words, pinyin, review state (no meanings, and review records cannot be changed) (GET /me/words; user token; scopes: me:words:readonly, me:words). */
    public function listMyWords($query = null, $options = [])
    {
        return $this->call('listMyWords', 'GET', '/me/words', [], $query, null, ['user'], $options);
    }

    /** 逐页读取 listMyWords 的全部 items；page_size 默认 100。 */
    public function listMyWordsAll($query = null, $options = [])
    {
        return $this->iterate('listMyWords', 'GET', '/me/words', [], $query, ['user'], $options);
    }

    /** Add a word to this person's word bank (unchanged if it is already there; its source is recorded as this app) (POST /me/words; user token; scopes: me:words). */
    public function addMyWord($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('addMyWord: body is required');
        return $this->call('addMyWord', 'POST', '/me/words', [], null, $body, ['user'], $options);
    }

    /** A class's exams (tenant tokens only, own institution) (GET /classes/{class_id}/exams; tenant token; scopes: grade:readonly). */
    public function listClassExams($classId, $query = null, $options = [])
    {
        return $this->call('listClassExams', 'GET', '/classes/{class_id}/exams', ['class_id' => $classId], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listClassExams 的全部 items；page_size 默认 100。 */
    public function listClassExamsAll($classId, $query = null, $options = [])
    {
        return $this->iterate('listClassExams', 'GET', '/classes/{class_id}/exams', ['class_id' => $classId], $query, ['tenant'], $options);
    }

    /** Each learner's result in one exam (scores only once released; tenant tokens only, own institution) (GET /classes/{class_id}/exams/{exam_id}/results; tenant token; scopes: grade:readonly). */
    public function listClassExamResults($classId, $examId, $query = null, $options = [])
    {
        return $this->call('listClassExamResults', 'GET', '/classes/{class_id}/exams/{exam_id}/results', ['class_id' => $classId, 'exam_id' => $examId], $query, null, ['tenant'], $options);
    }

    /** 逐页读取 listClassExamResults 的全部 items；page_size 默认 100。 */
    public function listClassExamResultsAll($classId, $examId, $query = null, $options = [])
    {
        return $this->iterate('listClassExamResults', 'GET', '/classes/{class_id}/exams/{exam_id}/results', ['class_id' => $classId, 'exam_id' => $examId], $query, ['tenant'], $options);
    }

    /** List assignable avatar frames (GET /gray/avatar-frames; app token; scopes: ). */
    public function listGrayAvatarFrames($query = null, $options = [])
    {
        return $this->call('listGrayAvatarFrames', 'GET', '/gray/avatar-frames', [], $query, null, ['app'], $options);
    }

    /** 逐页读取 listGrayAvatarFrames 的全部 items；page_size 默认 100。 */
    public function listGrayAvatarFramesAll($query = null, $options = [])
    {
        return $this->iterate('listGrayAvatarFrames', 'GET', '/gray/avatar-frames', [], $query, ['app'], $options);
    }

    /** Temporarily set an avatar frame for any platform user (POST /gray/avatar-frame-assignments; app token; scopes: ). */
    public function assignGrayAvatarFrame($body = null, $options = [])
    {
        if ($body === null) throw new \InvalidArgumentException('assignGrayAvatarFrame: body is required');
        return $this->call('assignGrayAvatarFrame', 'POST', '/gray/avatar-frame-assignments', [], null, $body, ['app'], $options);
    }

    /** Count posts by topic across all five regions (GET /gray/community/post-counts; app token; scopes: ). */
    public function getGrayCommunityPostCounts($query = null, $options = [])
    {
        return $this->call('getGrayCommunityPostCounts', 'GET', '/gray/community/post-counts', [], $query, null, ['app'], $options);
    }
}
