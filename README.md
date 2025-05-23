# Project: SmartFeedback – AI-Powered Automated Feedback (`assignfeedback_smartfeedback`)

## Overview

This project involves the development of a Moodle _Assignment Feedback_ plugin, named `assignfeedback_smartfeedback`. Its core functionality is to automatically generate high-quality, constructive feedback for student submissions using Artificial Intelligence (via the OpenAI API), immediately upon submission, without requiring any initial teacher intervention.

## Objective

To create a fully integrated system within Moodle that allows teachers to upload instructions and reference materials for the AI, enabling students to receive instant, personalized, and detailed feedback as soon as they submit their assignments.

## Key Features

-   **Automatic Trigger**  
    Feedback generation is triggered by the `submission_submitted` event as soon as a student submits their assignment.

-   **Intelligent Feedback Generation**  
    The AI analyzes the submitted content and provides:

    -   Highlighted strengths and positive aspects.
    -   Clear, actionable suggestions for improvement.
    -   Feedback aligned with the learning objectives defined by the teacher.

-   **Teacher’s Role**  
    Teachers still provide the final grade and may review, modify, or supplement the AI-generated feedback as needed.

-   **Multi-format Support**  
    Planned support for both online text and file submissions.

-   **Privacy and Transparency**  
    Implements Moodle’s privacy API to ensure responsible use of user data.

## Technical Architecture

-   Built as a `assignfeedback`-type plugin in Moodle.
-   Uses Moodle’s event system to hook into the assignment submission process.
-   Includes interface options for teachers to provide correction guidelines, rubrics, or reference materials.
-   Integrates with OpenAI’s API for text analysis and feedback generation.

## Expected Benefits

-   Enhances the student learning experience by delivering timely, formative feedback.
-   Reduces teacher workload by automating the initial feedback process.
-   Encourages learner autonomy through immediate, actionable insights.
