<?php
$chatbot_suggestions = array(
    'What courses do you offer?',
    'How do I register?',
    'How do I join online classes?',
    'Do you provide certificates?',
    'What are the course fees?',
    'How can I contact support?',
    'Do you provide placement assistance?',
    'How do I become a trainer?',
);
?>

<div class="lvalues-chatbot" data-chatbot-endpoint="<?php echo site_url('chatbot/ask'); ?>">
    <button type="button" class="lvalues-chatbot__launcher" aria-label="Open Lvalues chatbot" aria-expanded="false">
        <i class="fas fa-comments"></i>
    </button>

    <section class="lvalues-chatbot__panel" aria-label="Lvalues chatbot assistant" aria-hidden="true">
        <header class="lvalues-chatbot__header">
            <div>
                <strong>Lvalues Assistant</strong>
                <span>FAQ support</span>
            </div>
            <button type="button" class="lvalues-chatbot__close" aria-label="Minimize chatbot">
                <i class="fas fa-minus"></i>
            </button>
        </header>

        <div class="lvalues-chatbot__messages" aria-live="polite">
            <div class="lvalues-chatbot__bubble lvalues-chatbot__bubble--bot">
                Hello 👋 Welcome to Lvalues. How can I help you today?
            </div>
        </div>

        <div class="lvalues-chatbot__suggestions" aria-label="Suggested questions">
            <?php foreach ($chatbot_suggestions as $suggestion): ?>
                <button type="button" data-chatbot-question="<?php echo html_escape($suggestion); ?>">
                    <?php echo html_escape($suggestion); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <form class="lvalues-chatbot__form">
            <input type="text" name="question" maxlength="500" autocomplete="off" placeholder="Type your question..." aria-label="Ask a question">
            <button type="submit" aria-label="Send question">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </section>
</div>