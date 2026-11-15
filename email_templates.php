<?php
/**
 * StudyLance Email Templates
 * Email template functions shared across the system
 */

/**
 * Generate job posting email template
 */
function generateJobPostedEmail($freelancerName, $job) {
    $jobTitle = htmlspecialchars($job['title']);
    $jobDescription = nl2br(htmlspecialchars($job['description']));
    $jobBudget = number_format($job['budget'], 2);
    $jobDate = date('F j, Y', strtotime($job['created_at']));
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 10px; }
            .job-title { color: #6a5cff; font-size: 24px; font-weight: bold; margin-bottom: 15px; }
            .job-budget { font-size: 20px; font-weight: bold; color: #27ae60; margin-bottom: 15px; }
            .job-description { margin-bottom: 20px; }
            .cta-button { display: inline-block; background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .footer { text-align: center; color: #666; margin-top: 30px; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎯 New Job Opportunity!</h1>
                <p>A new job has been posted that matches your skills</p>
            </div>
            
            <div class='content'>
                <p>Hello $freelancerName,</p>
                <p>Great news! A new job has been posted on StudyLance that might interest you:</p>
                
                <div class='job-title'>$jobTitle</div>
                <div class='job-budget'>💰 Budget: ₹$jobBudget</div>
                <div class='job-description'>$jobDescription</div>
                
                <p><strong>Posted on:</strong> $jobDate</p>
                
                <p>This is a great opportunity to showcase your skills and earn money. Don't wait too long - good jobs get snapped up quickly!</p>
                
                <div style='text-align: center;'>
                    <a href='http://localhost/studylance/get_jobs.php' class='cta-button'>
                        🚀 View Job Details & Apply
                    </a>
                </div>
                
                <p>Best of luck with your application!</p>
                <p>Warm regards,<br>The StudyLance Team</p>
            </div>
            
            <div class='footer'>
                <p>© 2024 StudyLance. All rights reserved.</p>
                <p>You're receiving this email because you're a registered freelancer on StudyLance.</p>
                <p>To unsubscribe, please update your notification preferences in your account settings.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Generate proposal received email template
 */
function generateProposalEmail($clientName, $proposal) {
    $freelancerName = htmlspecialchars($proposal['freelancer_name']);
    $clientName = htmlspecialchars($proposal['client_name']);
    $jobTitle = htmlspecialchars($proposal['job_title']);
    $bidAmount = number_format($proposal['bid_amount'], 2);
    $proposalText = nl2br(htmlspecialchars($proposal['proposal_text']));
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 10px; }
            .proposal-title { color: #6a5cff; font-size: 24px; font-weight: bold; margin-bottom: 15px; }
            .proposal-amount { font-size: 20px; font-weight: bold; color: #27ae60; margin-bottom: 15px; }
            .proposal-text { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
            .cta-button { display: inline-block; background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .footer { text-align: center; color: #666; margin-top: 30px; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📄 New Proposal Received!</h1>
                <p>A freelancer has applied to your job</p>
            </div>
            
            <div class='content'>
                <p>Hello $clientName,</p>
                <p>Great news! You've received a new proposal for your job:</p>
                
                <div class='proposal-title'>$jobTitle</div>
                <div class='proposal-amount'>💰 Bid Amount: ₹$bidAmount</div>
                
                <h3>Proposal Details:</h3>
                <div class='proposal-text'>$proposalText</div>
                
                <p><strong>Freelancer:</strong> $freelancerName</p>
                
                <p>Review the proposal carefully and decide if this freelancer is the right fit for your project.</p>
                
                <div style='text-align: center;'>
                    <a href='http://localhost/studylance/client_dashboard_new.php' class='cta-button'>
                        👀 Review Proposal
                    </a>
                </div>
                
                <p>Best regards,<br>The StudyLance Team</p>
            </div>
            
            <div class='footer'>
                <p>© 2024 StudyLance. All rights reserved.</p>
                <p>You're receiving this email because you're a registered client on StudyLance.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Generate proposal status update email template
 */
function generateProposalStatusEmail($freelancerName, $proposal) {
    $jobTitle = htmlspecialchars($proposal['job_title']);
    $clientName = htmlspecialchars($proposal['client_name']);
    $status = ucfirst($proposal['status']);
    $bidAmount = number_format($proposal['bid_amount'], 2);
    
    $statusColor = $proposal['status'] === 'accepted' ? '#27ae60' : '#e74c3c';
    $statusIcon = $proposal['status'] === 'accepted' ? '✅' : '❌';
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 10px; }
            .status-badge { display: inline-block; background: $statusColor; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 15px 0; }
            .job-title { color: #6a5cff; font-size: 20px; font-weight: bold; margin-bottom: 10px; }
            .proposal-amount { font-size: 18px; font-weight: bold; color: #27ae60; margin-bottom: 15px; }
            .cta-button { display: inline-block; background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .footer { text-align: center; color: #666; margin-top: 30px; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>$statusIcon Proposal Status Update!</h1>
                <p>Your proposal has been reviewed</p>
            </div>
            
            <div class='content'>
                <p>Hello $freelancerName,</p>
                <p>There's an update on your proposal for:</p>
                
                <div class='job-title'>$jobTitle</div>
                <div class='proposal-amount'>💰 Your Bid: ₹$bidAmount</div>
                
                <div class='status-badge'>
                    Status: $status
                </div>
                
                <p><strong>Client:</strong> $clientName</p>
                
                " . ($proposal['status'] === 'accepted' ? 
                    "<p>Congratulations! The client has accepted your proposal. You can now contact the client to discuss the project details and get started.</p>" :
                    "<p>The client has decided to move forward with another candidate. Don't be discouraged - there are many other opportunities available on StudyLance.</p>") . "
                
                <div style='text-align: center;'>
                    <a href='http://localhost/studylance/freelancer_dashboard_new.php' class='cta-button'>
                        📊 View Dashboard
                    </a>
                </div>
                
                <p>Best regards,<br>The StudyLance Team</p>
            </div>
            
            <div class='footer'>
                <p>© 2024 StudyLance. All rights reserved.</p>
                <p>You're receiving this email because you're a registered freelancer on StudyLance.</p>
            </div>
        </div>
    </body>
    </html>";
}
?>
