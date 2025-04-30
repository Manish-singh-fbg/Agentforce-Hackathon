public with sharing class AnswerGenerate implements Queueable, Database.AllowsCallouts{
    private String caseId;
    private String prompt;
    public AnswerGenerate(String prompt, String caseId)  {
        this.caseId = caseId;
        this.prompt = prompt;
    }

    public void execute(QueueableContext context) {
        try {
            // 1. Perform the callout to the Gen AI API.
            generateAnswerFuture( prompt,  caseId);
        } catch (Exception e) {
            System.debug('Exception in GenerateAIResponseQueueable: ' + e.getMessage());
        }
    }

    public static void generateAnswerFuture(String prompt, String caseId) {
        // Create generations request
        aiplatform.ModelsAPI.createGenerations_Request request = new aiplatform.ModelsAPI.createGenerations_Request();

        // Specify model
        request.modelName = 'sfdc_ai__DefaultOpenAIGPT4OmniMini';

        // Create request body
        aiplatform.ModelsAPI_GenerationRequest body = new aiplatform.ModelsAPI_GenerationRequest();
        request.body = body;

        // Add prompt to body
        body.prompt = prompt;

        String generatedText = null;
        try {
            // Make request
            aiplatform.ModelsAPI modelsAPI = new aiplatform.ModelsAPI();
            aiplatform.ModelsAPI.createGenerations_Response response = modelsAPI.createGenerations(request);

            // Extract generated text
            if (response.Code200 != null && response.Code200.generation != null) {
                generatedText = response.Code200.generation.generatedText;
            } else {
                System.debug('Error: No generated text in the AI response for Case ID: ' + caseId);
            }

        // Handle error
        } catch(aiplatform.ModelsAPI.createGenerations_ResponseException e) {
            System.debug('Response code for Case ID ' + caseId + ': ' + e.responseCode);
            System.debug('The following exception occurred for Case ID ' + caseId + ': ' + e);
            // Optionally log the error or handle it in another way
        } catch (Exception e) {
            System.debug('An unexpected error occurred for Case ID ' + caseId + ': ' + e);
        }

        if (String.isNotBlank(generatedText) && caseId != null) {   
            try {
                List<Case> casesToUpdate = [SELECT Id, Reply__c ,CaseNumber, Question__c, Question__r.Name, Review__c, Review__r.Name  FROM Case WHERE CaseNumber = :caseId ];
                if (!casesToUpdate.isEmpty()) {
                    InnovateElectronics__c settings = InnovateElectronics__c.getInstance('Default'); // Assuming a Custom Setting
                    String apiKey = settings != null ? settings.ApiKey__c: null;
                    Case caseToUpdate = casesToUpdate[0];
                    if(caseToUpdate.Review__c != null && caseToUpdate.Review__r.Name !=null){
                        String rid = caseToUpdate.Review__r.Name;
                        String endpoint = 'https://shop.gositemaker.com/api/reviews.php';
                        ReviewRequestWrapper requestWrapper = new ReviewRequestWrapper();
                        requestWrapper.review_id = Integer.valueOf(rid);
                        requestWrapper.responder_name = 'InnovateElectronics Customer Service';
                        requestWrapper.response_text = generatedText;
        
                        // 3. Serialize the wrapper object to JSON.
                        String requestBody = JSON.serialize(requestWrapper);
                        HttpRequest req = new HttpRequest();
        
                        req.setTimeout(60000); //milliseconds (2 minutes)
                        req.setHeader('Content-Type', 'application/json');
                        req.setHeader('Accept', 'application/json');
                        req.setHeader('x-api-key', apiKey);
                        req.setEndpoint(endpoint); // Add other parameters as needed
                        req.setMethod('POST');
                        req.setBody(requestBody);
                        Http http = new Http();
                        HttpResponse res = null;
                        String responseBody;
                        res = http.send(req);
                        caseToUpdate.Reply__c = generatedText;
                        caseToUpdate.Status = 'Closed';
                        update caseToUpdate;
                        
                    }else if(caseToUpdate.Question__c != null && caseToUpdate.Question__r.Name !=null){
                        String qid = caseToUpdate.Question__r.Name;
                        String qendpoint = 'https://shop.gositemaker.com/api/questions.php';
                        QuestionRequestWrapper requestWrapperq = new QuestionRequestWrapper();
                        requestWrapperq.question_id = Integer.valueOf(qid);
                        requestWrapperq.responder_name = 'InnovateElectronics Customer Service';
                        requestWrapperq.answer_text = generatedText;
        
                        // 3. Serialize the wrapper object to JSON.
                        String qrequestBody = JSON.serialize(requestWrapperq);
                        HttpRequest req = new HttpRequest();
        
                        req.setTimeout(60000); //milliseconds (2 minutes)
                        req.setHeader('Content-Type', 'application/json');
                        req.setHeader('Accept', 'application/json');
                        req.setHeader('x-api-key', apiKey);
                        req.setEndpoint(qendpoint); // Add other parameters as needed
                        req.setMethod('POST');
                        req.setBody(qrequestBody);
                        Http http = new Http();
                        HttpResponse res = null;
                        String responseBody;
                        res = http.send(req);
                        //if (res.getStatusCode() == 200) {
                            System.debug('getStatusCode' + res.getStatusCode());
                            caseToUpdate.Reply__c = generatedText;
                            caseToUpdate.Status = 'Closed';
                            update caseToUpdate;
                     //   }
                    }
                }  
            } catch (DmlException e) {
                System.debug('Error updating Case ' + caseId + ': ' + e.getMessage());
            }
        } else {
            System.debug('Warning: Could not update Case ' + caseId + '. Generated text is empty or Case ID is null.');
        }
    }

    public class ReviewRequestWrapper {
        public Integer review_id;
        public String responder_name;
        public String response_text;
    }

    public class QuestionRequestWrapper {
        public Integer question_id;
        public String responder_name;
        public String answer_text;
    }
}