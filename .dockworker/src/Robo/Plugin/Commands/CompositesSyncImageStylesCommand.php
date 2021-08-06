<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DrupalKubernetesPodTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerDeploymentCommands;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines the commands used to interact with a deployed Drupal application.
 */
class CompositesSyncImageStylesCommand extends DockworkerDeploymentCommands {

  use DrupalKubernetesPodTrait;

  /**
   * Synchronize composites.lib.unb.ca image styles from live instances.
   *
   * @param string $env
   *   The environment to obtain the logs from. Defaults to 'prod'.
   *
   * @command composites:sync:image-styles
   * @usage composites:sync:image-styles
   *
   * @throws \Exception
   *
   * @kubectl
   */
  public function synchronizeCommitCompositesImageStylesFromLive($env = 'prod') {
    $this->setRunOtherCommand("local:config:remote-sync $env");
    $this->stageAllCompositesImageStyles();
    $this->say("Committing Changes...");
    $this->repoGit->commit('Update composites image styles [skip ci]', ['--no-verify']);
  }

  /**
   * Stages all changes in composites image styles to the local repository.
   */
  protected function stageAllCompositesImageStyles() {
    $this->say("Searching for image styles...");
    $image_style_files = glob("{$this->repoRoot}/config-yml/image.style.*composite_crop*");
    if (!empty($image_style_files)) {
      $this->say("Adding new/changed image styles...");
      $progressBar = new ProgressBar($this->output, count($image_style_files));
      $progressBar->start();
      foreach ($image_style_files as $image_style_file) {
        $this->repoGit->addFile($image_style_file);
        $progressBar->advance();
      }
      $progressBar->finish();
    }
  }

}
