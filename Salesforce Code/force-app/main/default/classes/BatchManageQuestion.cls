global class BatchManageQuestion implements Database.Batchable<String>, Database.AllowsCallouts, Database.Stateful  {

    private String apiKey;
    private List<String> productIds; // You'll need to define how to get these

    public BatchManageQuestion(String apiKey, List<String> productIds) {
        this.apiKey = apiKey;
        this.productIds = productIds;
    }

    global Iterable<String> start(Database.BatchableContext bc) {
        return productIds; // Iterate over the list of product IDs
    }

    global void execute(Database.BatchableContext bc, List<String> scope) {
        ManageReviewQuestion mq = new ManageReviewQuestion();
        for (String productId : scope) {
            String questions = ManageReviewQuestion.fetchQuestions(productId, apiKey);
            ManageReviewQuestion.processQuestions(questions,productId);
        }
    }

    global void finish(Database.BatchableContext bc) {
       
    }
}
