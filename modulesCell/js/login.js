// function logoutUser() {
//     var rootUrl = $("#hidden_rooturl").val(); // Get the root URL from the hidden input
//     if (!rootUrl) {
//         console.error("Root URL is not defined.");
//         alertmessage("error", "An unexpected error occurred. Please try again.");
//         return false;
//     }
//     $.ajax({
//         url: rootUrl + 'login/logout.php', // Logout endpoint
//         type: 'POST',
//         data: {}, // Add necessary data if required
//         dataType:'JSON',
//         success: function (response) {
//             if(response.status === "Success") {
//                 alertmessage("success", "You have been logged out successfully.");
//                 window.location.href = rootUrl + '/index.php'; // Redirect to the home page
//             } else {
//                 alertmessage("error", response.message || "An error occurred while logging out. Please try again.");
//             }
//         },
//         error: function (xhr, status, error) {
//             console.error('Logout failed:', error);
//             alertmessage("error", "A server error occurred while logging out. Please try again.");
//         }
//     });
// }