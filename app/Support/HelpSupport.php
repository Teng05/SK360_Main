<?php

namespace App\Support;

class HelpSupport
{
    /** Role-specific instructions for existing web workflows; no database access. */
    public static function topics(string $role): array
    {
        $common = [
            'home' => ['Home & recent activity', [
                'Use Home to review recent posts and activity. Open a post to read its details, then use the interaction controls available on the page.',
                'Use the sidebar to switch modules. On smaller screens, open the menu first.',
            ]],
            'announcements' => ['Announcements', [
                'Search or filter announcements, then open the relevant post. Use the comment and reaction controls where available.',
                'Only edit or delete posts when the page provides those actions for your account.',
            ]],
            'calendar' => ['Calendar & schedules', [
                'Use the date and view controls to browse schedules. Open an event to review its details.',
                'Check the submission slot itself for its reporting period, availability, and final deadline.',
            ]],
            'chat' => ['Chat & messages', [
                'Search for an available official, select the conversation, and enter your message. Check that you selected the correct recipient before sending.',
                'Never send passwords or verification codes through chat.',
            ]],
            'meetings' => ['Meetings & video calls', [
                'Open Meetings to review the schedule and agenda. Use the available join control for a video meeting and allow browser access to your camera and microphone when needed.',
                'If joining fails, check your internet connection and browser permissions. Call participation is recorded by the system.',
            ]],
            'rankings' => ['Rankings & points', [
                'Choose an available ranking period to review the leaderboard and the displayed score breakdown.',
                'Points follow the system rules for approved required documents, late submissions, and meeting attendance. Scores cannot be entered manually on this page. Recording a financial expense is separate from document approval.',
            ]],
            'notifications' => ['Notifications & deadline reminders', [
                'Open the bell in the top bar and select a notification to open its related item. A submission highlighted after navigation returns to its normal color after about 10 seconds.',
                'Submission reminders target outstanding submissions or required corrections during the final 3 days and on the due date. Check the displayed deadline even if you have not received a reminder.',
                'If a notification refers to an unavailable item, check whether the slot closed or the administration changed, then contact the responsible official.',
            ]],
            'profile' => ['Profile & contact verification', [
                'Open Profile Settings to update your available profile fields or picture. Your name is locked; ask the responsible administrator if it needs correction.',
                'To change email or phone, choose one contact detail, enter your current password, and request a verification code. Enter the code sent to the new address or number before the change is saved.',
                'Philippine mobile numbers must use 09XXXXXXXXX or +639XXXXXXXXX. A valid format alone does not prove that the number is active; SMS verification confirms access.',
                'Use the Security section to change your password. Never share passwords or verification codes.',
            ]],
            'setup' => ['Account setup & expired links', [
                'Open the latest account setup email and choose Set Up My Account. Create and confirm your password to activate the account. Setup links expire after 24 hours.',
                'A resent setup link replaces the previous link. If the latest link has expired, request another invitation. Check spam or junk folders if the email is missing.',
                'Chairmen should contact the SK Federation President; Secretaries should contact their SK Chairman. An account with an invalid administration assignment needs administrator attention before activation.',
            ]],
            'public' => ['Public portal & financial transparency', [
                'The public portal provides announcements, leadership information, rankings, the published LYDP when available, and financial information.',
                'On Annual Budget & LYDP, select a fiscal year and barangay, then open View Financial Summary. Project expense totals are grouped by individual project.',
                'Annual allocations require publication authorization. Active reported expenses appear automatically. The proof eye button opens a supporting file in a new tab only when its current version has been explicitly marked suitable for public disclosure.',
                'Private, historical, or deleted expense proofs are not available to public users.',
            ]],
            'support' => ['Troubleshooting & getting support', [
                'For a missing submission slot, confirm your role, barangay, reporting period, and whether the slot is open. For a rejected upload, follow the file type and size shown beside that field.',
                'If an action fails, keep the error message and note the page, action, and time. Include a screenshot when reporting the problem, but hide passwords, codes, and sensitive financial information.',
                'Contact your SK Chairman for Secretary account or barangay concerns. Contact the SK Federation President for Chairman accounts, submission slots, allocations, or document review concerns. Technical delivery or system errors should be referred to the system administrator.',
            ]],
        ];
        $documents = ['Budget Submissions & financial documents', [
            'Choose an available budget submission slot and check its title, document category, reporting period, and deadline. Upload a PDF (up to 5 MB), or use the system template where offered.',
            'Categories include Annual Budget, Supplemental Budget, and Financial / COA Report. COA slots may be monthly, quarterly, semi-annual, or annual. Direct-entry templates can be viewed or downloaded as generated PDFs.',
            'Annual Budget and annual COA submissions do not require a second entry of the annual allocation amount. Report line-item amounts remain part of the document; they do not independently change the monitoring balance.',
            'Check the review status and remarks. A corrected document can replace an existing submission only when revision is requested. Approved documents cannot be replaced.',
        ]];
        $reports = ['Accomplishment reports', [
            'Open Reports, choose an available slot assigned to your role, and check its reporting period and deadline. Attach the requested PDF and submit.',
            'A pending submission awaits review. If marked Needs Revision, read the remarks and resubmit the corrected PDF. Approved submissions cannot be replaced.',
        ]];
        $archive = ['Archive & previous administrations', [
            'Use Archive to find previous-administration records. Filter by administration, type, year, barangay where offered, or search for a title.',
            'Use the eye button to view a supported file in a new tab, or download it. Gray disabled controls indicate that no accessible file is available. Generated reports use their available PDF controls.',
            'Financial Records appear under the Budget type. Open the record details or its available proof links. Previous-administration financial records are read-only; current-administration deleted expenses may also appear here.',
            'Financial proof links are separate from the regular document bulk download. Archive access remains restricted to your role and, for Chairmen, your barangay.',
        ]];
        $specific = match ($role) {
            'sk_president' => [
                'dashboard' => ['Dashboard & financial charts', [
                    'Use the available year and barangay filters to review reporting summaries and charts.',
                    'Annual Budget by Barangay uses the annual allocations recorded in Budget & Finance, including corrections. Budget Utilization compares active reported expenses with those allocations; it is not a measure of cash on hand.',
                ]],
                'finances' => ['Budget & Finance overview', [
                    'Use Overview, Allocations, Expenses, Public Transparency, and History. Filter by fiscal year and barangay; Clear restores the defaults when filters differ.',
                    'Record each barangay annual allotment under Allocations. Correct an allocation using the available correction action and provide a reason; the original amount and history are retained.',
                    'Estimated remaining budget equals the effective allotment minus active submitted reported expenses. Pending-review expenses count once; drafts and cancelled or deleted expenses are excluded. Cheque releases may occur separately, so the balance is a monitoring estimate.',
                    'Current finance pages show the current administration. Use Archive for earlier administrations.',
                ]],
                'expenses' => ['Monitoring barangay expenses', [
                    'Open Expenses and filter the barangay and fiscal year. Open an expense to inspect its project, amount, date, description, reference, and available supporting proof.',
                    'Chairmen record their own barangay expenses. The President monitors them rather than encoding expenses on their behalf. Individual expense approval is not required for the monitoring balance to update.',
                    'Document quality review remains a separate workflow in Consolidation.',
                ]],
                'publication' => ['Publishing annual allocations', [
                    'Open Budget & Finance > Public Transparency, choose Review & Publish Allocation, and review the information before authorizing publication.',
                    'Annual allocations and their corrections are published separately from automatically reported expenses. After a correction, review and publish the updated allocation so the public summary reflects it.',
                    'Use Public Portal Preview to check what visitors can see. Private proofs remain restricted unless their current version is explicitly authorized for public disclosure.',
                ]],
                'consolidation' => ['Consolidation & document quality review', [
                    'Find accomplishment or budget/COA submissions by barangay and the available filters. Open the document before recording a quality review.',
                    'Mark it Approved or Needs Revision. Provide remarks for requested corrections so the submitting official knows what to fix. Quality approval and ranking rules are separate from financial expense monitoring.',
                    'Use individual or available bulk download controls to obtain documents.',
                ]],
                'module' => ['Module Management & submission slots', [
                    'Create a submission slot with the document category, reporting period, title, assigned role, opening date, and deadline. Only the assigned officials can use that slot.',
                    'Use the displayed controls to update, close, reopen, or delete eligible slots. Check existing submissions before changing a slot. Availability and review rules still apply to resubmissions.',
                    'Upload the LYDP PDF through its available management control to make it available on the public Annual Budget & LYDP page.',
                ]],
                'user-management' => ['User Management & setup invitations', [
                    'Use Add Chairmen to enter first and last names, email, optional Philippine mobile number, and an available barangay. You can also use Import CSV and its provided template.',
                    'Pending means account setup has not been completed. Use the individual resend action when a setup link expires.',
                    'When more than 3 non-archived official accounts are Pending, Resend Setup Links appears beside the SK Chairmen panel arrow. Confirm to send eligible invitations. The result reports sent, failed, or skipped accounts. This bulk action is limited to once per 10 minutes.',
                    'Bulk invitations only go to Chairman or President accounts with valid pending assignments in the current administration. Secretary invitations are managed by their Chairman.',
                    'Use available account status, history, and reappointment controls carefully. Start New Term manages administration handover; the outgoing President remains caretaker until the successor completes setup.',
                ]],
                'leadership' => ['Leadership directory', ['Browse barangay leadership and official information. Manage Chairman accounts through User Management; barangay council and Secretary management belongs to the Chairman.']],
                'archive' => $archive,
            ],
            'sk_chairman' => [
                'finances' => ['Budget overview', [
                    'Open Budget from the sidebar, then choose Overview, Expenses, Budget Submissions, or History. Use fiscal-year filters for the current administration; Clear restores default filters.',
                    'Your annual allotment is encoded by the SK Federation President. You can view it but cannot change it. Ask the President if the allocation is missing or incorrect.',
                    'Estimated remaining budget equals the effective allotment minus active submitted reported expenses. Submitted expenses count immediately and once. Deleted expenses are excluded. This monitoring estimate is not actual cash available from cheque releases.',
                    'Use Archive for previous-administration finances.',
                ]],
                'expenses' => ['Recording & correcting expenses', [
                    'Choose Record Expense after an annual allotment is available. Select a Related Project, or choose + Add New Project and enter its name and category. + Add New Category saves a custom category with the new reusable project.',
                    'Enter a positive amount, expense date, description, and receipt/reference number when available. Upload mandatory proof: PDF, JPEG, or PNG up to 5 MB. Your barangay and submitter identity come from your account.',
                    'After saving, the expense updates the monitoring balance automatically. Reuse the same project for later related expenses so project totals remain grouped correctly.',
                    'Open your expense details to edit or delete using the available actions. Editing changes the existing expense rather than adding a second expense. A replacement proof is optional; leaving it empty retains the current proof. Deleted expenses and historical proofs remain available through authorized records.',
                    'Check the public disclosure confirmation only when the proof is safe to publish. Expense details are publicly reported, but unchecked proofs stay private.',
                ]],
                'budget' => $documents,
                'reports' => $reports,
                'leadership' => ['Managing barangay leadership', [
                    'Manage councilor and Treasurer records and the Secretary account using the available add, edit, status, delete, and reappointment controls. Council records do not create a separate Treasurer login.',
                    'Use the Secretary resend setup action when their 24-hour invitation expires. Management is restricted to your assigned barangay.',
                ]],
                'archive' => $archive,
            ],
            'sk_secretary' => [
                'budget' => $documents,
                'reports' => $reports,
                'leadership' => ['Barangay leadership directory', ['Review your barangay leadership information. This view is read-only for Secretaries. Ask your Chairman to correct council or Secretary records.']],
                'finance-access' => ['Financial documentation permissions', [
                    'You can submit the financial documents assigned to your role and barangay through Budget. Follow the slot reporting period, document requirements, and review remarks.',
                    'Allocation encoding and expense recording are separate from document submission. Secretaries do not receive allocation or expense-recording authority through this module. Contact your Chairman for those concerns.',
                ]],
            ],
            default => [],
        };
        if (!$specific) return [];
        if ($role === 'sk_president') {
            $common['meetings'][1][] = 'Use meeting management to schedule or update eligible meetings. Finish the meeting before using the manual attendance action; review attendance carefully because ranking rules use it.';
        }
        return $specific + $common;
    }

    public static function currentTopic(string $route, string $section = ''): ?string
    {
        // Archive financial detail routes must resolve to Archive, not live finances.
        if (str_contains($route, '.archive')) return 'archive';
        if (str_contains($route, '.finances')) return match ($section) {
            'expenses' => 'expenses', 'transparency' => 'publication', 'submissions', 'documents' => 'budget', default => 'finances',
        };
        foreach (['user-management', 'consolidation', 'module', 'dashboard', 'reports', 'budget', 'rankings', 'meetings', 'leadership', 'profile', 'announcements', 'calendar', 'chat', 'home'] as $topic) {
            if (str_contains($route, '.'.$topic)) return $topic;
        }
        return null;
    }
}
