public with sharing class GenerateAIAnswerAction {

    @InvocableMethod(label='Generate AI Answer (Future)' description='Use the Models API to generate text asynchronously and update the Case.')
    public static List<FlowOutput> createGenerationFuture(List<FlowInput> inputVariables) {
        List<FlowOutput> results = new List<FlowOutput>();
        if (!inputVariables.isEmpty()) {
            FlowInput input = inputVariables[0];
            if (input.caseId != null && String.isNotBlank(input.prompt)) {
                System.enqueueJob(new AnswerGenerate(input.prompt, input.caseId));
               // generateAnswerFuture(input.prompt, input.caseId);
                results.add(new FlowOutput('AI answer generation initiated for Case: ' + input.caseId));
            } else {
                results.add(new FlowOutput('Error: Case ID or prompt is missing.'));
            }
        } else {
            results.add(new FlowOutput('Error: No input provided.'));
        }
        return results;
    }
    
    public class FlowInput{
        @InvocableVariable public String prompt;
        @InvocableVariable public String caseId; // Case ID as InvocableVariable
    }
    public class FlowOutput{
        @InvocableVariable public String response;
        public FlowOutput(String response) {
            this.response = response;
        }
    }
}
