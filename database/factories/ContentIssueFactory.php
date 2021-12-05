<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\ContentIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentIssueFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ContentIssue::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'content_id' => Content::factory(),
            'title' => $this->faker->unique()->sentence(4),
            'views' => 0,
            'is_available' => 1,
            'description' => <<<HTML
            <p><img style="display: block; margin-left: auto; margin-right: auto;" title="Tiny Logo" src="https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png" alt="TinyMCE Logo" width="128" height="128" /></p>
            <h2 style="text-align: center;">Welcome to the TinyMCE editor demo!</h2>
          
            <h2>Got questions or need help?</h2>
          
            <ul>
              <li>Our <a href="https://www.tiny.cloud/docs/">documentation</a> is a great resource for learning how to configure TinyMCE.</li>
              <li>Have a specific question? Try the <a href="https://stackoverflow.com/questions/tagged/tinymce" target="_blank" rel="noopener"><code>tinymce</code> tag at Stack Overflow</a>.</li>
              <li>We also offer enterprise grade support as part of <a href="https://www.tiny.cloud/pricing">TinyMCE premium plans</a>.</li>
            </ul>
          
            <h2>A simple table to play with</h2>
          
            <table style="border-collapse: collapse; width: 100%;" border="1">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Cost</th>
                  <th>Really?</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>TinyMCE</td>
                  <td>Free</td>
                  <td>YES!</td>
                </tr>
                <tr>
                  <td>Plupload</td>
                  <td>Free</td>
                  <td>YES!</td>
                </tr>
              </tbody>
            </table>
          
            <h2>Found a bug?</h2>
          
            <p>
              If you think you have found a bug please create an issue on the <a href="https://github.com/tinymce/tinymce/issues">GitHub repo</a> to report it to the developers.
            </p>
          
            <h2>Finally ...</h2>
          
            <p>
              Don't forget to check out our other product <a href="http://www.plupload.com" target="_blank">Plupload</a>, your ultimate upload solution featuring HTML5 upload support.
            </p>
            <p>
              Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team.
            </p>
            HTML,
        ];
    }
}
