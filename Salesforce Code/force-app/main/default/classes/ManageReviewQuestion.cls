public with sharing class ManageReviewQuestion {

    private static final String BASE_URL = 'https://shop.gositemaker.com'; // Adjust if needed

    // Method to fetch questions for a specific product ID
    public static String fetchQuestions(String productId, String apiKey) {
        String endpoint = BASE_URL + '/api/questions.php?product_id='+productId;
        HttpRequest req = new HttpRequest();
        req.setTimeout(60000); //milliseconds (2 minutes)
        req.setHeader('Content-Type', 'application/json');
        req.setHeader('Accept', 'application/json');
        req.setHeader('x-api-key', apiKey);
        req.setEndpoint(endpoint); // Add other parameters as needed
        req.setMethod('GET');
        return processCallout(req);
    }

    // Method to fetch reviews for a specific product ID
    public static String fetchReviews(String productId, String apiKey) {
        String endpoint = BASE_URL + '/api/reviews.php?product_id='+productId;
        HttpRequest req = new HttpRequest();
        req.setTimeout(60000); //milliseconds (2 minutes)
        req.setHeader('Content-Type', 'application/json');
        req.setHeader('Accept', 'application/json');
        req.setHeader('x-api-key', apiKey);
        req.setEndpoint(endpoint); // Add other parameters
        req.setMethod('GET');
        return processCallout(req);
    }

    private static String processCallout(HttpRequest req) {
        Http http = new Http();
        HttpResponse res = null;
        String responseBody;
        try {
            res = http.send(req);
            if (res.getStatusCode() == 200) {
                responseBody = res.getBody();
                
            } else {
                System.debug('Error fetching '  + '. Status Code: ' + res.getStatusCode() + ', Body: ' + res.getBody());
                // Consider throwing an exception or logging the error more formally
            }
        } catch (CalloutException e) {
            System.debug('Callout error fetching '  + ': ' + e.getMessage());
            // Consider throwing an exception or logging the error formally
        } catch (JSONException e) {
            System.debug('JSON parsing error for '  + ': ' + e.getMessage());
            System.debug('Response Body: ' + res.getBody());
            // Consider throwing an exception or logging the error formally
        }
        return responseBody;
    }

    // Method to process fetched questions and create/update Salesforce records
    public static void processQuestions(String questions , String p) {
        Product2 ps = [SELECT ID, ProductCode FROM Product2 WHERE ProductCode = :p LIMIT 1];
        QuestionWrapper parsedQuestion= parseQues(questions);
        List<Question__c> questionsToUpsert = new List<Question__c>();
        if (parsedQuestion != null && parsedQuestion.status == 'success' && parsedQuestion.data != null) {
            
            for (QuestionData question : parsedQuestion.data) {
                Question__c ques = new Question__c();
                ques.Name = String.valueOf(question.id);
                ques.Product__c = ps.Id;
                ques.Customer_Name__c = question.user_name;
               // rev.UserName__c = review.user_name;
                ques.Question__c = question.question_text;
                System.debug('Question ID: ' + question.id);
                System.debug('Product ID: ' + question.product_id);
                System.debug('User Name: ' + question.user_name);
                System.debug('Question: ' + question.question_text);

                if (question.answers != null) {
                    for (QuestionAnswer ans : question.answers) {
                        ques.Answer__c = ans.answer_text;
                    }
                } else {
                    System.debug('  No responses found for this review.');
                }
                System.debug('---');
                questionsToUpsert.add(ques);
            }
        } else {
            System.debug('Invalid JSON response or status is not "success".');
        }
        
        if (!questionsToUpsert.isEmpty()) {
            //upsert questionsToUpsert ;
            Database.upsert(questionsToUpsert, Question__c.Name, false);
        }
    }

    // Method to process fetched reviews and create/update Salesforce records
    public static void processReviews(String reviews, String p) {
        ResponseWrapper parsedResponse = parse(reviews);
        Product2 ps = [SELECT ID, ProductCode FROM Product2 WHERE ProductCode = :p LIMIT 1];
        List<Review__c> reviewsToUpsert = new List<Review__c>();
        if (parsedResponse != null && parsedResponse.status == 'success' && parsedResponse.data != null) {
            
            for (ReviewData review : parsedResponse.data) {
                Review__c rev = new Review__c();
                rev.Name = String.valueOf(review.id);
                rev.Product__c = ps.Id;
                rev.Customer_Name__c = review.user_name;
                rev.Rating__c = review.rating;
                rev.Review__c = review.review_text;
                System.debug('Review ID: ' + review.id);
                System.debug('Product ID: ' + review.product_id);
                System.debug('User Name: ' + review.user_name);
                System.debug('Rating: ' + review.rating);
                System.debug('Review Text: ' + review.review_text);
               // System.debug('Created At (Review): ' + review.created_at);

                if (review.responses != null) {
                    for (ReviewResponse response : review.responses) {
                        rev.Response__c = response.response_text;
                        System.debug('  Response ID: ' + response.id);
                        System.debug('  Responder Name: ' + response.responder_name);
                        System.debug('  Response Text: ' + response.response_text);
                        System.debug('  Source: ' + response.source);
                       // System.debug('  Created At (Response): ' + response.created_at);
                    }
                } else {
                    System.debug('  No responses found for this review.');
                }
                System.debug('---');
                reviewsToUpsert.add(rev);
            }
        } else {
            System.debug('Invalid JSON response or status is not "success".');
        }
        
        if (!reviewsToUpsert.isEmpty()) {
            Database.upsert(reviewsToUpsert, Review__c.Name, false);
            upsert reviewsToUpsert ;
        }
    }


    public static ResponseWrapper parse(String jsonString) {
        try {
            ResponseWrapper wrapper = (ResponseWrapper) System.JSON.deserialize(jsonString, ResponseWrapper.class);
            return wrapper;
        } catch (JSONException e) {
            System.debug('Error parsing JSON: ' + e.getMessage());
            return null; // Or throw the exception, depending on your error handling strategy
        }
    }

    public static QuestionWrapper parseQues(String jsonString) {
        try {
            QuestionWrapper wrapper = (QuestionWrapper) System.JSON.deserialize(jsonString, QuestionWrapper.class);
            return wrapper;
        } catch (JSONException e) {
            System.debug('Error parsing JSON: ' + e.getMessage());
            return null; // Or throw the exception, depending on your error handling strategy
        }
    }
    public class ResponseWrapper {
        public String status { get; set; }
        public List<ReviewData> data { get; set; }
    }

    public class ReviewData {
        public Integer id { get; set; }
        public Integer product_id { get; set; }
        public String user_name { get; set; }
        public Integer rating { get; set; }
        public String review_text { get; set; }
        //public Datetime created_at { get; set; }
        public List<ReviewResponse> responses { get; set; }
    }

    public class ReviewResponse {
        public Integer id { get; set; }
        public String responder_name { get; set; }
        public String response_text { get; set; }
        public String source { get; set; }
       // public Datetime created_at { get; set; }
    }

    public class QuestionWrapper {
        public String status { get; set; }
        public List<QuestionData> data { get; set; }
    }

    public class QuestionData {
        public Integer id { get; set; }
        public Integer product_id { get; set; }
        public String user_name { get; set; }
        public String question_text { get; set; }
       // public Datetime created_at { get; set; }
        public List<QuestionAnswer> answers { get; set; }
    }

    public class QuestionAnswer {
        public Integer id { get; set; }
        public String responder_name { get; set; }
        public String answer_text { get; set; }
        public String source { get; set; }
        //public Datetime created_at { get; set; }
    }
}